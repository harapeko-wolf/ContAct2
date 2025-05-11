import ViewPageContent from './ViewPageContent';
import { generateStaticParams } from './generateStaticParams';

export { generateStaticParams };

export default function ViewPage({ params }: { params: { uuid: string } }) {
  return <ViewPageContent uuid={params.uuid} />;
}