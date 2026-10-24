import { BrowserRouter, Route, Routes, useLocation } from 'react-router-dom';
import { useEffect } from 'react';
import Header from './components/Header';
import Footer from './components/Footer';
import Home from './pages/Home';
import Register from './pages/Register';
import AdminPortal from './admin/AdminPortal';

function ScrollToTop() {
  const { pathname, hash } = useLocation();
  useEffect(() => {
    if (hash) {
      const id = hash.slice(1);
      window.setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    } else {
      window.scrollTo({ top: 0, behavior: 'auto' });
    }
  }, [pathname, hash]);
  return null;
}

function AppLayout() {
  const location = useLocation();
  if (location.pathname.startsWith('/admin')) return <><ScrollToTop /><AdminPortal /></>;
  return <><ScrollToTop /><Header /><Routes><Route path="/" element={<Home />} /><Route path="/register" element={<Register />} /><Route path="*" element={<Home />} /></Routes><Footer /></>;
}

export default function App() {
  return <BrowserRouter><AppLayout /></BrowserRouter>;
}
