import { Outlet } from "react-router-dom";
import { DashboardTopbar } from "../app/components/layout/DashboardTopbar";
import { Sidebar } from "../app/components/layout/Sidebar";
import { useAuth } from "../lib/auth-context";

export function DashboardLayout() {
  const { user } = useAuth();
  if (!user) return null;

  return (
    <div className="min-h-screen flex flex-col" style={{ background: "#EDF0F2" }}>
      <DashboardTopbar />
      <div className="flex flex-1 min-h-0">
        <Sidebar role={user.role} />
        <div className="flex-1 flex flex-col min-w-0">
          <main className="flex-1 overflow-y-auto p-7">
            <Outlet />
          </main>
          <footer
            className="px-7 py-3 flex items-center justify-between text-[11.5px] border-t"
            style={{ background: "#fff", borderColor: "#E1E5E9", color: "#5B6B84" }}
          >
            <span>Copyright © 2026 SunuConference.</span>
            <span>
              Version <b>1.0</b>
            </span>
          </footer>
        </div>
      </div>
    </div>
  );
}
