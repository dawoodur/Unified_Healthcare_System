import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import Appointments from './pages/Appointments';
import Prescription from './pages/Prescription';
import Availability from './pages/Availability';
import Leave from './pages/Leave';
import Records from './pages/Records';
import Inbox from './pages/Inbox';
import Reviews from './pages/Reviews';
import Analytics from './pages/Analytics';
import Profile from './pages/Profile';
import Notifications from './pages/Notifications';
import Consultation from './pages/Consultation';
import Career from './pages/Career';
import ReportIssue from './pages/ReportIssue';

export default function App() {
  return (
    <BrowserRouter basename={window.DOCTOR_APP_BASE}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/appointments" element={<Appointments />} />
        <Route path="/appointments/:appointmentId/prescription" element={<Prescription />} />
        <Route path="/availability" element={<Availability />} />
        <Route path="/availability/create" element={<Navigate to="/availability" replace />} />
        <Route path="/leave" element={<Leave />} />
        <Route path="/records" element={<Records />} />
        <Route path="/inbox" element={<Inbox />} />
        <Route path="/reviews" element={<Reviews />} />
        <Route path="/analytics" element={<Analytics />} />
        <Route path="/profile" element={<Profile />} />
        <Route path="/notifications" element={<Notifications />} />
        <Route path="/consultation/:appointmentId" element={<Consultation />} />
        <Route path="/career" element={<Career />} />
        <Route path="/report-issue" element={<ReportIssue />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}