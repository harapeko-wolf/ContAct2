# AWS環境でのCron自動化

## 概要
AWS環境では**手動crontab設定不要**で自動化できます。

## 🚀 **方法1: ECS Scheduled Tasks（推奨）**

### CloudFormation設定
```yaml
# cloudformation/scheduled-tasks.yml
AWSTemplateFormatVersion: '2010-09-09'
Resources:
  # フォローアップメール処理用スケジュールタスク
  FollowupEmailScheduleRule:
    Type: AWS::Events::Rule
    Properties:
      Name: followup-email-schedule
      Description: "フォローアップメール処理を毎分実行"
      ScheduleExpression: "rate(1 minute)"
      State: ENABLED
      Targets:
        - Arn: !Sub "${ECSCluster.Arn}"
          Id: FollowupEmailTarget
          RoleArn: !GetAtt ECSTaskExecutionRole.Arn
          EcsParameters:
            TaskDefinitionArn: !Ref FollowupEmailTaskDefinition
            LaunchType: FARGATE
            NetworkConfiguration:
              AwsVpcConfiguration:
                Subnets:
                  - !Ref PrivateSubnet1
                  - !Ref PrivateSubnet2
                SecurityGroups:
                  - !Ref ECSSecurityGroup

  # タスク定義
  FollowupEmailTaskDefinition:
    Type: AWS::ECS::TaskDefinition
    Properties:
      Family: followup-email-task
      Cpu: 256
      Memory: 512
      NetworkMode: awsvpc
      RequiresCompatibilities:
        - FARGATE
      ExecutionRoleArn: !Ref ECSTaskExecutionRole
      ContainerDefinitions:
        - Name: followup-email-container
          Image: !Sub "${AWS::AccountId}.dkr.ecr.${AWS::Region}.amazonaws.com/contact2-app:latest"
          Command:
            - "php"
            - "artisan"
            - "followup:process-emails"
          Environment:
            - Name: APP_ENV
              Value: production
            - Name: DB_HOST
              Value: !GetAtt RDSInstance.Endpoint.Address
          LogConfiguration:
            LogDriver: awslogs
            Options:
              awslogs-group: !Ref LogGroup
              awslogs-region: !Ref AWS::Region
              awslogs-stream-prefix: followup-email
```

### Terraform設定
```hcl
# terraform/scheduled_tasks.tf
resource "aws_cloudwatch_event_rule" "followup_email_schedule" {
  name                = "followup-email-schedule"
  description         = "フォローアップメール処理を毎分実行"
  schedule_expression = "rate(1 minute)"
}

resource "aws_cloudwatch_event_target" "followup_email_target" {
  rule     = aws_cloudwatch_event_rule.followup_email_schedule.name
  arn      = aws_ecs_cluster.main.arn
  role_arn = aws_iam_role.ecs_task_execution_role.arn

  ecs_target {
    task_definition_arn = aws_ecs_task_definition.followup_email.arn
    launch_type         = "FARGATE"

    network_configuration {
      subnets         = var.private_subnet_ids
      security_groups = [aws_security_group.ecs.id]
    }
  }
}

resource "aws_ecs_task_definition" "followup_email" {
  family                   = "followup-email-task"
  requires_compatibilities = ["FARGATE"]
  network_mode            = "awsvpc"
  cpu                     = 256
  memory                  = 512
  execution_role_arn      = aws_iam_role.ecs_task_execution_role.arn

  container_definitions = jsonencode([
    {
      name  = "followup-email-container"
      image = "${var.ecr_repository_url}:latest"
      command = ["php", "artisan", "followup:process-emails"]
      
      environment = [
        {
          name  = "APP_ENV"
          value = "production"
        },
        {
          name  = "DB_HOST"
          value = aws_rds_cluster.main.endpoint
        }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.app.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "followup-email"
        }
      }
    }
  ])
}
```

## 🛠️ **方法2: GitHub Actions自動デプロイ**

### デプロイ時にCron自動設定
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to EC2 and setup Cron
        uses: appleboy/ssh-action@v0.1.7
        with:
          host: ${{ secrets.EC2_HOST }}
          username: ${{ secrets.EC2_USER }}
          key: ${{ secrets.EC2_SSH_KEY }}
          script: |
            # アプリケーションデプロイ
            cd /var/www/contact2
            git pull origin main
            docker compose -f docker-compose.prod.yml up -d --build
            
            # Cron自動設定（手動設定不要）
            echo "Cron is handled by Docker container automatically"
            docker compose -f docker-compose.prod.yml logs cron
```

## 🏗️ **方法3: Ansible自動化**

```yaml
# ansible/playbook.yml
- name: Deploy Contact2 Application
  hosts: production
  tasks:
    - name: Deploy application with Docker
      docker_compose:
        project_src: /var/www/contact2
        files:
          - docker-compose.prod.yml
        state: present
        pull: yes
        
    # Cron設定は不要（Dockerで自動化）
    - name: Verify Cron container is running
      shell: docker compose -f docker-compose.prod.yml ps cron
      register: cron_status
      
    - debug:
        msg: "Cron container status: {{ cron_status.stdout }}"
```

## 🔄 **方法4: Kubernetes CronJob**

```yaml
# k8s/cronjob.yml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: followup-email-job
spec:
  schedule: "* * * * *"  # 毎分実行
  jobTemplate:
    spec:
      template:
        spec:
          containers:
          - name: followup-email
            image: contact2-app:latest
            command:
            - php
            - artisan
            - followup:process-emails
            env:
            - name: APP_ENV
              value: "production"
            - name: DB_HOST
              valueFrom:
                secretKeyRef:
                  name: app-secrets
                  key: db-host
          restartPolicy: OnFailure
```

## 📊 **デプロイ方法別比較**

| 方法 | 設定の複雑さ | 自動化レベル | コスト | 推奨度 |
|------|-------------|-------------|--------|---------|
| Docker本番 | ⭐⭐ | ⭐⭐⭐⭐⭐ | 💰💰 | 🟢 推奨 |
| ECS Scheduled | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 💰💰💰 | 🟢 推奨 |
| GitHub Actions | ⭐⭐ | ⭐⭐⭐⭐ | 💰 | 🟡 普通 |
| Ansible | ⭐⭐⭐ | ⭐⭐⭐⭐ | 💰💰 | 🟡 普通 |
| Kubernetes | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 💰💰💰💰 | 🔴 大規模向け |

## ✅ **推奨パターン**

### 小〜中規模プロジェクト
```bash
# Docker本番環境（手動設定ゼロ）
docker compose -f docker-compose.prod.yml up -d
```

### AWS環境
```bash
# ECS Scheduled Tasks（完全自動化）
terraform apply
# または
aws cloudformation deploy --template-file scheduled-tasks.yml
```

## 🎯 **結論**

**手動crontab設定は不要にできます！**

- ✅ **Docker本番**: 設定不要で自動Cron
- ✅ **AWS ECS**: Scheduled Tasksで完全自動化  
- ✅ **CI/CD**: デプロイ時に自動設定
- ✅ **IaC**: インフラ構築時に自動設定

**開発環境と同じように、本番でも手動設定なしで動作します。** 