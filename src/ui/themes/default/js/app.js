import "./jquery.js";
import I18n from "./i18n.js";
import moment from "moment";
import whbChartjs from "./chartjs.js";

// Import CSS for Webpack (order IS important!)
import '../css/base.css';
import '../css/theme.css';
import '../css/app.css';
import '../css/dropdown.css';

window.i18n = new I18n(LANGUAGE, CURRENCY);
