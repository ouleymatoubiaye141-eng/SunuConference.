import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";
import { Toaster } from "sonner";
import { AuthProvider } from "./lib/auth-context";
import { ProtectedRoute, GuestOnlyRoute } from "./routes/ProtectedRoute";
import { PublicLayout } from "./routes/PublicLayout";
import { DashboardLayout } from "./routes/DashboardLayout";

import { LandingPage } from "./pages/public/LandingPage";
import { CfpPage } from "./pages/public/CfpPage";
import { ProgrammePage } from "./pages/public/ProgrammePage";
import { LoginPage } from "./pages/public/LoginPage";
import { RegisterPage } from "./pages/public/RegisterPage";
import { ForgotPasswordPage } from "./pages/public/ForgotPasswordPage";
import { ResetPasswordPage } from "./pages/public/ResetPasswordPage";
import { ForcedPasswordChangePage } from "./pages/public/ForcedPasswordChangePage";
import { ProfilePage } from "./pages/ProfilePage";

import { AuthorDashboardPage } from "./pages/author/AuthorDashboardPage";
import { AuthorNewSubmissionPage } from "./pages/author/AuthorNewSubmissionPage";
import { AuthorSubmissionDetailPage } from "./pages/author/AuthorSubmissionDetailPage";
import { ReviewerDashboardPage } from "./pages/reviewer/ReviewerDashboardPage";
import { ReviewerSubmissionDetailPage } from "./pages/reviewer/ReviewerSubmissionDetailPage";
import { ChairDashboardPage } from "./pages/chair/ChairDashboardPage";
import { ChairCreateConferencePage } from "./pages/chair/ChairCreateConferencePage";
import { ChairEditConferencePage } from "./pages/chair/ChairEditConferencePage";
import { ChairReviewersPage } from "./pages/chair/ChairReviewersPage";
import { ChairTopicsPage } from "./pages/chair/ChairTopicsPage";
import { ChairSubmissionsPage } from "./pages/chair/ChairSubmissionsPage";
import { ChairSubmissionDetailPage } from "./pages/chair/ChairSubmissionDetailPage";
import { ChairSessionsPage } from "./pages/chair/ChairSessionsPage";
import { ChairPublishPage } from "./pages/chair/ChairPublishPage";
import { AdminDashboardPage } from "./pages/admin/AdminDashboardPage";
import { AdminPendingConferencesPage } from "./pages/admin/AdminPendingConferencesPage";
import { AdminConferencesPage } from "./pages/admin/AdminConferencesPage";
import { AdminUsersPage } from "./pages/admin/AdminUsersPage";
import { ComingSoonPage } from "./app/components/common/ComingSoonPage";

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Toaster position="top-right" richColors />
        <Routes>
          {/* Pages publiques (avec navbar) */}
          <Route element={<PublicLayout />}>
            <Route path="/" element={<LandingPage />} />
            <Route path="/appel-a-communications" element={<CfpPage />} />
            <Route path="/programme" element={<ProgrammePage />} />
          </Route>

          {/* Authentification (plein écran, sans navbar) */}
          <Route path="/connexion" element={<GuestOnlyRoute><LoginPage /></GuestOnlyRoute>} />
          <Route path="/inscription" element={<GuestOnlyRoute><RegisterPage /></GuestOnlyRoute>} />
          <Route path="/mot-de-passe-oublie" element={<GuestOnlyRoute><ForgotPasswordPage /></GuestOnlyRoute>} />
          <Route path="/reset-password" element={<GuestOnlyRoute><ResetPasswordPage /></GuestOnlyRoute>} />

          {/* Comptes connectés, sans navbar/sidebar */}
          <Route
            path="/changer-mot-de-passe"
            element={
              <ProtectedRoute>
                <ForcedPasswordChangePage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profil"
            element={
              <ProtectedRoute>
                <ProfilePage />
              </ProtectedRoute>
            }
          />

          {/* Espace Auteur */}
          <Route
            path="/auteur"
            element={
              <ProtectedRoute allow={["author"]}>
                <DashboardLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<AuthorDashboardPage />} />
            <Route path="nouvelle-soumission" element={<AuthorNewSubmissionPage />} />
            <Route path="soumissions/:id" element={<AuthorSubmissionDetailPage />} />
          </Route>

          {/* Espace Relecteur */}
          <Route
            path="/relecteur"
            element={
              <ProtectedRoute allow={["reviewer"]}>
                <DashboardLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<ReviewerDashboardPage />} />
            <Route path="soumissions/:id" element={<ReviewerSubmissionDetailPage />} />
          </Route>

          {/* Espace PC Chair */}
          <Route
            path="/chair"
            element={
              <ProtectedRoute allow={["chair"]}>
                <DashboardLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<ChairDashboardPage />} />
            <Route path="soumissions" element={<ChairSubmissionsPage />} />
            <Route path="soumissions/:id" element={<ChairSubmissionDetailPage />} />
            <Route path="relecteurs" element={<ChairReviewersPage />} />
            <Route path="assignations" element={<ChairTopicsPage />} />
            <Route path="sessions" element={<ChairSessionsPage />} />
            <Route path="comite" element={<ComingSoonPage title="Comité" desc="La gestion du comité de programme arrive dans une prochaine livraison." />} />
            <Route path="dates-limites" element={<ChairEditConferencePage />} />
            <Route path="publier" element={<ChairPublishPage />} />
            <Route path="statistiques" element={<ComingSoonPage title="Statistiques" desc="Les statistiques de la conférence arrivent dans une prochaine livraison." />} />
            <Route path="creer" element={<ChairCreateConferencePage />} />
          </Route>

          {/* Espace Admin */}
          <Route
            path="/admin"
            element={
              <ProtectedRoute allow={["admin"]}>
                <DashboardLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<AdminDashboardPage />} />
            <Route path="en-attente" element={<AdminPendingConferencesPage />} />
            <Route path="conferences" element={<AdminConferencesPage />} />
            <Route path="utilisateurs" element={<AdminUsersPage />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
