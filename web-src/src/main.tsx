import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./css/variables.css";
import "./css/style.css";
import "./css/responsive.css";
import App from "./App";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <App />
  </StrictMode>
);
