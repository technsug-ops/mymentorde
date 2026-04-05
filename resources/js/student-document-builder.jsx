import React from 'react';
import ReactDOM from 'react-dom/client';
import CVBuilderApp from './student-cv-builder/App.jsx';
import './student-cv-builder/index.css';

const rootEl = document.getElementById('student-cv-builder-root');
if (rootEl) {
  const bridge = window.__STUDENT_CV_BUILDER__ || {};
  ReactDOM.createRoot(rootEl).render(
    <React.StrictMode>
      <CVBuilderApp bridge={bridge} />
    </React.StrictMode>
  );
}
