import EditCompanyForm from './EditCompanyForm';
import AdminLayout from '@/components/admin/layout';

export default async function EditCompanyPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return (
    <AdminLayout>
      <EditCompanyForm companyId={id} />
    </AdminLayout>
  );
} 