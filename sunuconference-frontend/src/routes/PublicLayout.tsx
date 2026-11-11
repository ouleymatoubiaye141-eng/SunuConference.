import { Outlet } from "react-router-dom";
import { Navbar } from "../app/components/layout/Navbar";

export function PublicLayout() {
  return (
    <div className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1">
        <Outlet />
      </div>
    </div>
  );
}
