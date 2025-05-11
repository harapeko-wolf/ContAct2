'use client';

import { Calendar, Clock, User } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
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
const accessLogs = [
  {
    id: 1,
    user: '山田太郎',
    document: '製品カタログ2025',
    accessDate: '2025-06-01 14:30',
    duration: '12分',
    ipAddress: '192.168.1.100',
  },
  {
    id: 2,
    user: '鈴木花子',
    document: '技術仕様書v2.0',
    accessDate: '2025-06-01 13:15',
    duration: '25分',
    ipAddress: '192.168.1.101',
  }
];

export default function SampleAccessLogPage() {
  return (
    <AdminLayout>
      <div className="flex-1 p-8 space-y-6">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-bold">アクセスログ（サンプル）</h1>
        </div>

        <Card>
          <CardContent className="p-6">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ユーザー</TableHead>
                  <TableHead>ドキュメント</TableHead>
                  <TableHead>アクセス日時</TableHead>
                  <TableHead>閲覧時間</TableHead>
                  <TableHead>IPアドレス</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {accessLogs.map((log) => (
                  <TableRow key={log.id}>
                    <TableCell className="flex items-center gap-2">
                      <User className="h-4 w-4" />
                      {log.user}
                    </TableCell>
                    <TableCell>{log.document}</TableCell>
                    <TableCell className="flex items-center gap-2">
                      <Calendar className="h-4 w-4" />
                      {log.accessDate}
                    </TableCell>
                    <TableCell className="flex items-center gap-2">
                      <Clock className="h-4 w-4" />
                      {log.duration}
                    </TableCell>
                    <TableCell>{log.ipAddress}</TableCell>
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