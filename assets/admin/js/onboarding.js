(function (w, d) {
  const Tours = w.PWPL_Tours || null;
  if (!Tours || !Tours.tours) {
    return;
  }

  const state = {
    activeTour: null,
    activeIndex: 0,
    steps: [],
    overlay: null,
    card: null,
    highlight: null,
  };

  const createEl = (tag, className) => {
    const el = d.createElement(tag);
    if (className) {
      el.className = className;
    }
    return el;
  };

  function ensureContainers() {
    if (!state.overlay) {
      state.overlay = createEl('div', 'pwpl-tour-overlay');
      state.overlay.tabIndex = -1;
      d.body.appendChild(state.overlay);
    }
    if (!state.card) {
      state.card = createEl('div', 'pwpl-tour-card');
      state.card.setAttribute('role', 'dialog');
      state.card.setAttribute('aria-modal', 'true');
      state.card.innerHTML = [
        '<div class="pwpl-tour-card__title" data-tour-title></div>',
        '<div class="pwpl-tour-card__body" data-tour-body></div>',
        '<div class="pwpl-tour-card__footer">',
        '  <button type="button" class="button pwpl-tour-prev" data-tour-prev>Back</button>',
        '  <div class="pwpl-tour-actions">',
        '    <a href="#" class="pwpl-tour-skip" data-tour-skip>Skip</a>',
        '    <button type="button" class="button button-primary" data-tour-next>Next</button>',
        '  </div>',
        '</div>',
      ].join('');
      d.body.appendChild(state.card);
      state.prevBtn = state.card.querySelector('[data-tour-prev]');
      state.nextBtn = state.card.querySelector('[data-tour-next]');
      state.skipLink = state.card.querySelector('[data-tour-skip]');
      state.titleEl = state.card.querySelector('[data-tour-title]');
      state.bodyEl = state.card.querySelector('[data-tour-body]');
      state.prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.PWPL_Onboarding.prevStep();
      });
      state.nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.PWPL_Onboarding.nextStep();
      });
      state.skipLink.addEventListener('click', (e) => {
        e.preventDefault();
        window.PWPL_Onboarding.endTour({ completed: false });
      });
      d.addEventListener('keydown', (e) => {
        if (state.activeTour && e.key === 'Escape') {
          window.PWPL_Onboarding.endTour({ completed: false });
        }
      });
    }
    if (!state.highlight) {
      state.highlight = createEl('div', 'pwpl-tour-highlight');
      d.body.appendChild(state.highlight);
    }
  }

  function saveState(tourId, status) {
    if (!Tours.ajaxUrl || !Tours.nonce) {
      return;
    }
    const form = new w.FormData();
    form.append('action', 'pwpl_save_tour_state');
    form.append('nonce', Tours.nonce);
    form.append('tour_id', tourId);
    form.append('status', status);
    w.fetch(Tours.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: form,
    }).catch(() => {});
  }

  function setCardPosition() {
    const cardRect = state.card.getBoundingClientRect();
    const top = w.scrollY + Math.max(20, (w.innerHeight - cardRect.height) / 2);
    const left = Math.max(10, (w.innerWidth - cardRect.width) / 2);
    state.card.style.top = `${top}px`;
    state.card.style.left = `${left}px`;
  }

  function renderStep() {
    const step = state.steps[ state.activeIndex ];
    if ( ! step ) {
      window.PWPL_Onboarding.endTour({ completed: false });
      return;
    }
    const target = d.querySelector(step.target);
    if ( ! target ) {
      // Skip missing targets.
      API.nextStep(true);
      return;
    }
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    state.overlay.style.display = 'block';
    state.card.style.display = 'block';
    state.highlight.style.display = 'block';

    const rect = target.getBoundingClientRect();
    state.highlight.style.width = `${rect.width}px`;
    state.highlight.style.height = `${rect.height}px`;
    state.highlight.style.top = `${rect.top + w.scrollY}px`;
    state.highlight.style.left = `${rect.left + w.scrollX}px`;

    state.titleEl.textContent = step.title || '';
    state.bodyEl.textContent = step.body || '';
    state.prevBtn.style.visibility = state.activeIndex === 0 ? 'hidden' : 'visible';
    state.nextBtn.textContent = state.activeIndex === state.steps.length - 1 ? (Tours.labels && Tours.labels.finish || 'Finish') : (Tours.labels && Tours.labels.next || 'Next');

    setCardPosition();
    state.card.focus({ preventScroll: true });
  }

  function clearUI() {
    if (state.overlay) state.overlay.style.display = 'none';
    if (state.card) state.card.style.display = 'none';
    if (state.highlight) state.highlight.style.display = 'none';
  }

  const API = {
    startTour: (tourId) => {
      const steps = (Tours.tours && Tours.tours[tourId]) || [];
      if (!steps.length) return;
      state.activeTour = tourId;
      state.steps = steps;
      state.activeIndex = 0;
      ensureContainers();
      renderStep();
      saveState(tourId, 'in_progress');
    },
    nextStep: (skipMissing) => {
      if (!state.steps.length) return;
      const nextIndex = state.activeIndex + 1;
      if (nextIndex >= state.steps.length) {
        API.endTour({ completed: true });
        return;
      }
      state.activeIndex = nextIndex;
      renderStep();
    },
    prevStep: () => {
      if (!state.steps.length) return;
      state.activeIndex = Math.max(state.activeIndex - 1, 0);
      renderStep();
    },
    endTour: ({ completed }) => {
      const tourId = state.activeTour;
      clearUI();
      state.activeTour = null;
      state.steps = [];
      state.activeIndex = 0;
      if (tourId) {
        saveState(tourId, completed ? 'completed' : 'dismissed');
      }
    },
  };

  w.PWPL_Onboarding = API;

  // Banner buttons (data-pwpl-tour-start / data-pwpl-tour-skip)
  d.addEventListener('click', (e) => {
    const startBtn = e.target.closest('[data-pwpl-tour-start]');
    const skipBtn = e.target.closest('[data-pwpl-tour-skip]');
    if (startBtn) {
      e.preventDefault();
      const tourId = startBtn.getAttribute('data-pwpl-tour-start');
      API.startTour(tourId);
      startBtn.closest('[data-pwpl-tour-banner]')?.remove();
    }
    if (skipBtn) {
      e.preventDefault();
      const tourId = skipBtn.getAttribute('data-pwpl-tour-skip');
      API.endTour({ completed: false });
      skipBtn.closest('[data-pwpl-tour-banner]')?.remove();
    }
  });
})(window, document);
