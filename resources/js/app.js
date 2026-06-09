import './bootstrap';

// CSP-safe Alpine build (D-048 / T9-1): keeps a strict Content-Security-Policy
// (no 'unsafe-eval'). Components must be registered via Alpine.data(...) — inline
// expression evaluation is intentionally not used. D-039 baseline > convenience.
import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.start();
