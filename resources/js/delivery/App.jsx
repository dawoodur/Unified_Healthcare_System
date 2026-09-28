import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import Available from './pages/Available';
import MyDeliveries from './pages/MyDeliveries';
import Reviews from './pages/Reviews';

export default function App() {
  return (
    <BrowserRouter basename={window.DELIVERY_APP_BASE}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/available" element={<Available />} />
        <Route path="/my-deliveries" element={<MyDeliveries />} />
        <Route path="/reviews" element={<Reviews />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
