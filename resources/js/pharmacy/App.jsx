import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import Inventory from './pages/Inventory';
import Orders from './pages/Orders';
import Reviews from './pages/Reviews';

export default function App() {
  return (
    <BrowserRouter basename={window.PHARMACY_APP_BASE}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/inventory" element={<Inventory />} />
        <Route path="/orders" element={<Orders />} />
        <Route path="/reviews" element={<Reviews />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
