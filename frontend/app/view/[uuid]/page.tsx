import ViewPageContent from './ViewPageContent';
import { generateStaticParams } from './generateStaticParams';

export { generateStaticParams };

export default async function ViewPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = await params;
  return <ViewPageContent uuid={uuid} />;
}