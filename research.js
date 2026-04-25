/* ============================================================
   RESEARCH PAGE — research.js
   1. Category filter
   2. BibTeX cite modal
   ============================================================ */

/* ── BibTeX entries ── */
const BIBTEX = {
  banglaqa26: `@inproceedings{hossain2026banglaqa,
  title     = {BanglaQA: A Large-Scale Question Answering Benchmark
               for the Bengali Language},
  author    = {Hossain, Tahsin and Begum, Fatema and
               Chowdhury, Lamia and Rahman, Md. Anisur},
  booktitle = {Proceedings of the 64th Annual Meeting of the
               Association for Computational Linguistics (ACL)},
  year      = {2026}
}`,
  campuseye25: `@inproceedings{rahman2025campuseye,
  title     = {CampusEye: Anchor-Free Multi-Object Tracking for
               Resource-Constrained Edge Deployment},
  author    = {Rahman, Arif and Hasan, Mehedi and Akter, Sadia},
  booktitle = {Proceedings of the IEEE/CVF International Conference
               on Computer Vision (ICCV)},
  year      = {2025}
}`,
  artforge25: `@inproceedings{islam2025artforge,
  title     = {Stylistic Latent Diffusion for Regional Folk Art
               Generation with Cultural Fidelity Metrics},
  author    = {Islam, Nadia and Akter, Sadia and Begum, Fatema},
  booktitle = {Proceedings of the IEEE/CVF Conference on Computer
               Vision and Pattern Recognition (CVPR)},
  year      = {2025}
}`,
  sentimentbd25: `@inproceedings{chowdhury2025sentimentbd,
  title     = {SentimentBD: A Benchmark for Aspect-Level Sentiment
               Analysis in Code-Mixed Bengali--English},
  author    = {Chowdhury, Lamia and Begum, Fatema and Joty, Shafiq},
  booktitle = {Proceedings of the 2025 Conference on Empirical
               Methods in Natural Language Processing (EMNLP)},
  year      = {2025}
}`,
  energypulse25: `@article{kabir2025energypulse,
  title   = {Temporal Fusion Transformers for Campus-Scale Energy
             Demand Forecasting with Uncertainty Quantification},
  author  = {Kabir, Raihan and Begum, Fatema and Hasan, Kamrul},
  journal = {Applied Energy},
  volume  = {358},
  pages   = {122401},
  year    = {2025}
}`,
  robonav25: `@inproceedings{hossain2025robonav,
  title     = {Domain Randomisation Strategies for Robust Sim-to-Real
               Transfer in Indoor Mobile Robot Navigation},
  author    = {Hossain, Tahsin and Kabir, Raihan and Hassan, Mahbub},
  booktitle = {Proceedings of the IEEE International Conference on
               Robotics and Automation (ICRA)},
  year      = {2025}
}`,
  bioner26: `@inproceedings{begum2026bioner,
  title     = {BioNER-BD: A Named Entity Recognition Corpus for
               Bangla Clinical and Biomedical Text},
  author    = {Begum, Fatema and Hossain, Tahsin and Sarker, Farhana},
  booktitle = {Proceedings of the BioNLP Workshop at ACL 2026},
  year      = {2026}
}`,
  flood25: `@inproceedings{rahman2025flood,
  title     = {Efficient Fine-Tuning of Vision Transformers for Flood
               Mapping from Low-Resolution Satellite Imagery},
  author    = {Rahman, Arif and Islam, Nadia},
  booktitle = {IEEE International Geoscience and Remote Sensing
               Symposium (IGARSS)},
  year      = {2025}
}`,
};

/* ── 1. Filter ── */
(function () {
  const filters  = document.querySelectorAll('#researchFilters .filter-btn');
  const cards    = document.querySelectorAll('.paper-card[data-category]');
  const emptyMsg = document.getElementById('researchEmpty');
  if (!filters.length) return;

  filters.forEach(btn => {
    btn.addEventListener('click', () => {
      filters.forEach(b => b.classList.remove('filter-btn--active'));
      btn.classList.add('filter-btn--active');

      const filter = btn.dataset.filter;
      let visible = 0;
      cards.forEach(card => {
        const match = filter === 'all' || card.dataset.category === filter;
        card.classList.toggle('paper-card--hidden', !match);
        if (match) visible++;
      });
      if (emptyMsg) emptyMsg.style.display = visible === 0 ? 'block' : 'none';
    });
  });
})();

/* ── 2. Cite modal ── */
(function () {
  const modal    = document.getElementById('citeModal');
  const codeEl   = document.getElementById('citeCode');
  const backdrop = document.getElementById('citeBackdrop');
  const closeBtn = document.getElementById('citeClose');
  const copyBtn  = document.getElementById('citeCopy');
  if (!modal) return;

  function openModal(id) {
    codeEl.textContent = BIBTEX[id] || '% Citation not found';
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    copyBtn.textContent = 'Copy BibTeX';
  }

  document.querySelectorAll('.paper-link--cite').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.id));
  });

  backdrop.addEventListener('click', closeModal);
  closeBtn.addEventListener('click', closeModal);

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  copyBtn.addEventListener('click', () => {
    navigator.clipboard.writeText(codeEl.textContent).then(() => {
      copyBtn.textContent = 'Copied!';
      setTimeout(() => { copyBtn.textContent = 'Copy BibTeX'; }, 2000);
    });
  });
})();
