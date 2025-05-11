'use client';

import { FileText, FileUp } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/components/admin/layout';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

// サンプルデータ
const pdfList = [
  {
    id: 1,
    title: '製品カタログ2025',
    pages: 15,
    uploadDate: '2025-06-01',
    fileSize: '3.2 MB',
    views: 245,
  },
  {
    id: 2,
    title: '技術仕様書v2.0',
    pages: 28,
    uploadDate: '2025-05-28',
    fileSize: '5.1 MB',
    views: 182,
  }
];

export default function SamplePDFsPage() {
  return (
    <AdminLayout>
      <div className="flex-1 p-8 space-y-6">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-bold">PDF資料管理（サンプル）</h1>
          <Button className="gap-2">
            <FileUp className="h-4 w-4" />
            新規アップロード
          </Button>
        </div>

        <Card>
          <CardContent className="p-6">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>タイトル</TableHead>
                  <TableHead>ページ数</TableHead>
                  <TableHead>アップロード日</TableHead>
                  <TableHead>ファイルサイズ</TableHead>
                  <TableHead>閲覧数</TableHead>
                  <TableHead>操作</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {pdfList.map((pdf) => (
                  <TableRow key={pdf.id}>
                    <TableCell className="font-medium">{pdf.title}</TableCell>
                    <TableCell>{pdf.pages}ページ</TableCell>
                    <TableCell>{pdf.uploadDate}</TableCell>
                    <TableCell>{pdf.fileSize}</TableCell>
                    <TableCell>{pdf.views}回</TableCell>
                    <TableCell>
                      <Button variant="outline" size="sm" className="gap-2">
                        <FileText className="h-4 w-4" />
                        表示
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}