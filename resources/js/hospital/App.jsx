import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import Doctors from './pages/Doctors';
import Facilities from './pages/Facilities';
import FacilityBookings from './pages/FacilityBookings';
import Operations from './pages/Operations';
import BloodRequests from './pages/BloodRequests';
import BloodDonations from './pages/BloodDonations';
import AppointmentStats from './pages/AppointmentStats';
import PaymentMethods from './pages/PaymentMethods';
import Reviews from './pages/Reviews';

export default function App() {
  return (
    <BrowserRouter basename={window.HOSPITAL_APP_BASE}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/doctors" element={<Doctors />} />
        <Route path="/facilities" element={<Facilities />} />
        <Route path="/facility-bookings" element={<FacilityBookings />} />
        <Route path="/operations" element={<Operations />} />
        <Route path="/blood-requests" element={<BloodRequests />} />
        <Route path="/blood-donations" element={<BloodDonations />} />
        <Route path="/appointment-stats" element={<AppointmentStats />} />
        <Route path="/payment-methods" element={<PaymentMethods />} />
        <Route path="/reviews" element={<Reviews />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
