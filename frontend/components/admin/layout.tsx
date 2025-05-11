'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { 
  BarChart3, 
  FileText, 
  Home, 
  LogOut, 
  Menu, 
  Settings, 
  FileStack,
  Users,
  X
} from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';

interface AdminLayoutProps {
  children: React.ReactNode;
}

export default function AdminLayout({ children }: AdminLayoutProps) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  const routes = [
    {
      icon: Home,
      href: '/admin/dashboard',
      label: 'ダッシュボード',
      active: pathname === '/admin/dashboard',
    },
    {
      icon: Users,
      href: '/admin/companies',
      label: '会社管理',
      active: pathname.includes('/admin/companies'),
    },
    {
      icon: FileStack,
      href: '/admin/pdf-templates',
      label: 'PDFテンプレート',
      active: pathname.includes('/admin/pdf-templates'),
    },
    {
      icon: Settings,
      href: '/admin/settings',
      label: '設定',
      active: pathname.includes('/admin/settings'),
    },
  ];

  return (
    <div className="flex min-h-screen">
      {/* Mobile Navigation */}
      <Sheet open={open} onOpenChange={setOpen}>
        <SheetTrigger asChild className="md:hidden fixed z-50 bottom-4 right-4">
          <Button size="icon" variant="default">
            <Menu />
          </Button>
        </SheetTrigger>
        <SheetContent side="left" className="p-0 w-64">
          <MobileNav routes={routes} setOpen={setOpen} />
        </SheetContent>
      </Sheet>

      {/* Desktop Navigation */}
      <div className="border-r bg-background hidden md:block">
        <DesktopNav routes={routes} />
      </div>

      {/* Content Area */}
      <main className="flex-1 h-full overflow-auto">
        {children}
      </main>
    </div>
  );
}

interface NavProps {
  routes: {
    label: string;
    icon: React.ElementType;
    href: string;
    active?: boolean;
  }[];
  setOpen?: (open: boolean) => void;
}

function MobileNav({ routes, setOpen }: NavProps) {
  return (
    <div className="h-full flex flex-col">
      <div className="p-4 border-b flex items-center justify-between">
        <Link 
          href="/admin/dashboard" 
          className="flex items-center gap-2 font-bold text-xl"
          onClick={() => setOpen?.(false)}
        >
          <FileText className="h-6 w-6 text-blue-600" />
          <span>ContAct</span>
        </Link>
        <Button 
          variant="ghost" 
          size="icon" 
          onClick={() => setOpen?.(false)}
        >
          <X className="h-5 w-5" />
        </Button>
      </div>
      <ScrollArea className="flex-1 p-4">
        <div className="space-y-1">
          {routes.map((route) => (
            <Link
              key={route.href}
              href={route.href}
              onClick={() => setOpen?.(false)}
              className={cn(
                "flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors",
                route.active 
                  ? "bg-blue-50 text-blue-600" 
                  : "text-muted-foreground hover:text-foreground hover:bg-gray-50"
              )}
            >
              <route.icon className="h-5 w-5" />
              {route.label}
            </Link>
          ))}
        </div>
      </ScrollArea>
      <div className="p-4 border-t">
        <Link href="/" className="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-gray-50 transition-colors">
          <LogOut className="h-5 w-5" />
          Logout
        </Link>
      </div>
    </div>
  );
}

function DesktopNav({ routes }: NavProps) {
  return (
    <div className="h-full flex flex-col">
      <div className="p-6">
        <Link href="/admin/dashboard" className="flex items-center gap-2 font-bold text-xl">
          <FileText className="h-6 w-6 text-blue-600" />
          <span>ContAct</span>
        </Link>
      </div>
      <ScrollArea className="flex-1 px-4">
        <div className="space-y-1">
          {routes.map((route) => (
            <Link
              key={route.href}
              href={route.href}
              className={cn(
                "flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors",
                route.active 
                  ? "bg-blue-50 text-blue-600" 
                  : "text-muted-foreground hover:text-foreground hover:bg-gray-50"
              )}
            >
              <route.icon className="h-5 w-5" />
              {route.label}
            </Link>
          ))}
        </div>
      </ScrollArea>
      <div className="p-4 border-t mt-auto">
        <Link href="/" className="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-gray-50 transition-colors">
          <LogOut className="h-5 w-5" />
          ログアウト
        </Link>
      </div>
    </div>
  );
}