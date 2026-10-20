import {
  Globe, Newspaper, ShoppingCart, Code, Store, MousePointerClick, LayoutGrid, Wrench, RefreshCw,
  MonitorSmartphone, Terminal, Server, Mail, Plane, BedDouble, HeartPulse, Building2, GraduationCap,
  HardHat, ArrowRight, Check, Phone, MapPin, Clock, Briefcase, Star, Search, PenTool, CodeXml, Rocket,
} from 'lucide-react';

const map = {
  Globe, Newspaper, ShoppingCart, Code, Store, MousePointerClick, LayoutGrid, Wrench, RefreshCw,
  MonitorSmartphone, Terminal, Server, Mail, Plane, BedDouble, HeartPulse, Building2, GraduationCap,
  HardHat, ArrowRight, Check, Phone, MapPin, Clock, Briefcase, Star, Search, PenTool, CodeXml, Rocket,
};

export default function Icon({ name, size = 24, className = '', strokeWidth = 1.6 }) {
  const C = map[name] ?? Globe;
  return <C size={size} strokeWidth={strokeWidth} className={className} aria-hidden="true" />;
}
