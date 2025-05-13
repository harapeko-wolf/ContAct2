'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { ArrowLeft, Loader2 } from 'lucide-react';

import AdminLayout from '@/components/admin/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import { companyApi } from '@/lib/api';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const companySchema = z.object({
  name: z.string().min(2, { message: '会社名は2文字以上必要です' }),
  email: z.string().email({ message: '有効なメールアドレスを入力してください' }),
  phone: z.string().optional(),
  address: z.string().optional(),
  website: z.string().url({ message: '有効なURLを入力してください' }).optional().or(z.literal('')),
  description: z.string().optional(),
  industry: z.string().optional(),
  employee_count: z.number().int().positive().optional(),
  status: z.enum(['active', 'considering', 'inactive']).default('active'),
});

type CompanyFormValues = z.infer<typeof companySchema>;

export default function CreateCompanyPage() {
  const [isSaving, setIsSaving] = useState(false);
  const router = useRouter();
  const { toast } = useToast();

  const form = useForm<CompanyFormValues>({
    resolver: zodResolver(companySchema),
    defaultValues: {
      name: '',
      email: '',
      phone: '',
      address: '',
      website: '',
      description: '',
      industry: '',
      employee_count: undefined,
      status: 'active',
    },
  });

  async function onSubmit(data: CompanyFormValues) {
    setIsSaving(true);
    
    try {
      await companyApi.create(data);
      
      toast({
        title: '成功',
        description: '会社を作成しました',
      });
      
      router.push('/admin/companies');
    } catch (error) {
      toast({
        title: 'エラー',
        description: '会社の作成に失敗しました',
        variant: 'destructive',
      });
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <AdminLayout>
      <div className="flex-1 p-8">
        <Button 
          variant="ghost" 
          className="gap-2 mb-6"
          onClick={() => router.push('/admin/companies')}
        >
          <ArrowLeft className="h-4 w-4" />
          会社一覧に戻る
        </Button>
        
        <div className="max-w-2xl mx-auto">
          <Card>
            <CardHeader>
              <CardTitle className="text-2xl">会社を追加</CardTitle>
              <CardDescription>
                新しい会社のプロフィール情報を入力
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                  <FormField
                    control={form.control}
                    name="name"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>会社名</FormLabel>
                        <FormControl>
                          <Input placeholder="株式会社テクノソリューション" {...field} disabled={isSaving} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <FormField
                      control={form.control}
                      name="email"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>メールアドレス</FormLabel>
                          <FormControl>
                            <Input placeholder="info@example.com" type="email" {...field} disabled={isSaving} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    
                    <FormField
                      control={form.control}
                      name="phone"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>電話番号（任意）</FormLabel>
                          <FormControl>
                            <Input placeholder="03-1234-5678" {...field} disabled={isSaving} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="address"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>住所（任意）</FormLabel>
                        <FormControl>
                          <Input placeholder="東京都千代田区丸の内1-1-1" {...field} disabled={isSaving} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="website"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>Webサイト（任意）</FormLabel>
                        <FormControl>
                          <Input placeholder="https://example.com" {...field} disabled={isSaving} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <FormField
                      control={form.control}
                      name="industry"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>業種（任意）</FormLabel>
                          <FormControl>
                            <Input placeholder="IT・通信" {...field} disabled={isSaving} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    
                    <FormField
                      control={form.control}
                      name="employee_count"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>従業員数（任意）</FormLabel>
                          <FormControl>
                            <Input 
                              type="number" 
                              placeholder="100" 
                              {...field} 
                              onChange={e => field.onChange(e.target.value ? parseInt(e.target.value) : undefined)}
                              disabled={isSaving} 
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={form.control}
                    name="status"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>ステータス</FormLabel>
                        <Select 
                          onValueChange={field.onChange} 
                          defaultValue={field.value}
                          disabled={isSaving}
                        >
                          <FormControl>
                            <SelectTrigger>
                              <SelectValue placeholder="ステータスを選択" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            <SelectItem value="active">受注</SelectItem>
                            <SelectItem value="considering">検討中</SelectItem>
                            <SelectItem value="inactive">失注</SelectItem>
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  
                  <FormField
                    control={form.control}
                    name="description"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>会社概要（任意）</FormLabel>
                        <FormControl>
                          <Textarea 
                            placeholder="会社の概要や特徴を入力してください" 
                            className="resize-none" 
                            rows={4}
                            {...field} 
                            disabled={isSaving}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  
                  <div className="flex gap-4">
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => router.push('/admin/companies')}
                      disabled={isSaving}
                    >
                      キャンセル
                    </Button>
                    <Button type="submit" disabled={isSaving}>
                      {isSaving ? (
                        <>
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" role="status" />
                          作成中...
                        </>
                      ) : (
                        '作成する'
                      )}
                    </Button>
                  </div>
                </form>
              </Form>
            </CardContent>
          </Card>
        </div>
      </div>
    </AdminLayout>
  );
}