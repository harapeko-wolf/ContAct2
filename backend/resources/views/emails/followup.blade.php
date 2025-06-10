<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>フォローアップメール</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 15px;
        }
        .document-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}より</h1>
        </div>

        <div class="content">
            <p>いつもお世話になっております。</p>

            <p>先ほどご覧いただいた資料はいかがでしたでしょうか？</p>

            <div class="document-info">
                <strong>ご確認いただいた資料：</strong><br>
                {{ $documentTitle }}
            </div>

            <p>
                ご質問やより詳しい内容についてお聞きになりたいことがございましたら、
                ぜひお気軽にお声がけください。
            </p>

            <p>
                下記のリンクより、お打ち合わせの日程をご調整いただけます。
                お忙しい中恐縮ですが、ご都合の良いお時間をお聞かせください。
            </p>

            <div style="text-align: center;">
                @if($bookingLink)
                    <a href="{{ $bookingLink }}?company_id={{ $companyId }}&guest_comment={{ $companyId }}" class="button">
                        候補の日時を確認する
                    </a>
                @else
                    <p style="color: #dc2626;">※ お打ち合わせをご希望の場合は、直接ご連絡ください</p>
                @endif
            </div>

            <p>
                何かご不明な点がございましたら、いつでもお気軽にお問い合わせください。
            </p>

            <p>
                今後ともどうぞよろしくお願いいたします。
            </p>
        </div>

        <div class="footer">
            <p>
                このメールは {{ $companyName }} より自動送信されています。<br>
                ContAct システムによる配信
            </p>
        </div>
    </div>
</body>
</html>
