import { FileText, FileUp } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/components/admin/layout';
import PDFListClient from './pdf-list-client';

// デモ用のサンプルデータ
const pdfList = [
  {
    id: 1,
    title: 'Q2 製品概要',
    pages: 8,
    uploadDate: '2025-05-15',
    fileSize: '2.4 MB',
    views: 127,
    visible: true,
  },
  {
    id: 2,
    title: '技術仕様書',
    pages: 12,
    uploadDate: '2025-05-10',
    fileSize: '3.8 MB',
    views: 89,
    visible: true,
  },
  {
    id: 3,
    title: '導入ガイド',
    pages: 15,
    uploadDate: '2025-05-08',
    fileSize: '4.2 MB',
    views: 64,
    visible: true,
  },
  {
    id: 4,
    title: 'ケーススタディ：エンタープライズ導入事例',
    pages: 20,
    uploadDate: '2025-05-01',
    fileSize: '5.6 MB',
    views: 42,
    visible: true,
  }
];

// 静的パラメータの生成
export async function generateStaticParams() {
  // サンプル用のIDを返す
  return [
    { id: '1' },
    { id: '2' },
    { id: '3' }
  ];
}

export default function CompanyPdfsPage({ params }: { params: { id: string } }) {
  return (
    <AdminLayout>
      <div className="flex-1 p-8">
        <PDFListClient companyId={params.id} />
        
        {/* アップロードカード */}
        {/* <Card className="border-dashed border-2 border-gray-200 bg-gray-50">
          <CardContent className="p-6 flex flex-col items-center justify-center h-full text-center">
            <FileUp className="h-10 w-10 text-gray-400 mb-4" />
            <h3 className="font-medium mb-1">新規資料をアップロード</h3>
            <p className="text-sm text-muted-foreground mb-4">PDFファイル（最大20MB）</p>
            <Button className="gap-2">
              <FileUp className="h-4 w-4" />
              PDFをアップロード
            </Button>
          </CardContent>
        </Card> */}
      </div>
    </AdminLayout>
  );
}