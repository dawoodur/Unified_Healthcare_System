import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import Users from './pages/Users';
import Transactions from './pages/Transactions';
import DoctorVerifications from './pages/DoctorVerifications';
import Reports from './pages/Reports';
import Analytics from './pages/Analytics';
import MedicineDrafts from './pages/MedicineDrafts';
import ScannedPrescriptions from './pages/ScannedPrescriptions';
import InboxHistory from './pages/InboxHistory';
import ConsultationChatHistory from './pages/ConsultationChatHistory';

export default function App() {
  return (
    <BrowserRouter basename={window.ADMIN_APP_BASE}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/users" element={<Users />} />
        <Route path="/transactions" element={<Transactions />} />
        <Route path="/doctor-verifications" element={<DoctorVerifications />} />
        <Route path="/reports" element={<Reports />} />
        <Route path="/analytics" element={<Analytics />} />
        <Route path="/medicine-drafts" element={<MedicineDrafts />} />
        <Route path="/scanned-prescriptions" element={<ScannedPrescriptions />} />
        <Route path="/inbox-history" element={<InboxHistory />} />
        <Route path="/consultation-chat-history" element={<ConsultationChatHistory />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
