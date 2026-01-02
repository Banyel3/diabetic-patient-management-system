import Sidebar from "@/components/Sidebar";
import AuthGuard from "@/components/AuthGuard";

export default function AppLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <AuthGuard>
      <div className="flex h-screen bg-background">
        <Sidebar />
        <main className="flex-1 overflow-y-auto">
          <div className="bg-surface rounded-3xl shadow-soft m-4 ml-0 p-6 min-h-[calc(100vh-2rem)]">
            {children}
          </div>
        </main>
      </div>
    </AuthGuard>
  );
}
