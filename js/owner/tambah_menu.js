/**
 * Tambah Menu JavaScript
 * Handles modal, filtering, and interactions
 */

document.addEventListener("DOMContentLoaded", function () {
  // ========== Toggle Status ==========
  const updateToggleText = (toggle, textEl, hiddenEl) => {
    textEl.textContent = toggle.checked ? "Aktif" : "Nonaktif";
    hiddenEl.value = toggle.checked ? "aktif" : "nonaktif";
  };

  // Add Modal Toggle
  const addToggle = document.getElementById("add_status_toggle");
  const addText = document.getElementById("add_status_text");
  const addHidden = document.getElementById("add_status");

  if (addToggle) {
    addToggle.addEventListener("change", () =>
      updateToggleText(addToggle, addText, addHidden)
    );
  }

  // Edit Modal Toggle
  const editToggle = document.getElementById("edit_status_toggle");
  const editText = document.getElementById("edit_status_text");
  const editHidden = document.getElementById("edit_status");

  if (editToggle) {
    editToggle.addEventListener("change", () =>
      updateToggleText(editToggle, editText, editHidden)
    );
  }

  // ========== Modal Management ==========
  function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.style.display = "flex";
      setTimeout(() => modal.classList.add("show"), 10);
    }
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove("show");
      setTimeout(() => {
        modal.style.display = "none";
        const form = modal.querySelector("form");
        if (form) {
          form.reset();
          // Reset toggle for add modal
          if (id === "addModal" && addToggle) {
            addToggle.checked = true;
            updateToggleText(addToggle, addText, addHidden);
          }
        }
      }, 200);
    }
  }

  // Open Add Modal
  const openModalBtn = document.getElementById("openModal");
  if (openModalBtn) {
    openModalBtn.onclick = () => openModal("addModal");
  }

  // Close buttons
  document.querySelectorAll(".close-btn").forEach((btn) => {
    btn.onclick = () => closeModal(btn.closest(".modal").id);
  });

  // Click outside modal to close
  window.onclick = (e) => {
    if (e.target.classList.contains("modal")) {
      closeModal(e.target.id);
    }
  };

  // ========== Edit Menu ==========
  document.querySelectorAll(".editBtn").forEach((btn) => {
    btn.onclick = function (e) {
      e.preventDefault();
      const card = this.closest(".card");

      document.getElementById("edit_id").value = card.dataset.id;
      document.getElementById("edit_nama").value = card.dataset.nama;
      document.getElementById("edit_kategori").value = card.dataset.kategori;
      document.getElementById("edit_harga").value = card.dataset.harga;

      const status = card.dataset.status;
      editToggle.checked = status === "aktif";
      updateToggleText(editToggle, editText, editHidden);

      openModal("editModal");
    };
  });

  // ========== Filter & Search ==========
  const tabs = document.querySelectorAll(".tab");
  const cards = document.querySelectorAll(".card");
  const searchInput = document.getElementById("searchMenu");

  tabs.forEach((tab) => {
    tab.onclick = () => {
      tabs.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      const filter = tab.dataset.filter;
      filterCards(filter, searchInput.value);
    };
  });

  if (searchInput) {
    searchInput.addEventListener("input", function () {
      const activeFilter = document.querySelector(".tab.active").dataset.filter;
      filterCards(activeFilter, this.value);
    });
  }

  function filterCards(category, search) {
    const lowerSearch = search.toLowerCase();
    let visibleCount = 0;

    cards.forEach((card) => {
      const matchesCategory =
        category === "semua" || card.dataset.kategori === category;
      const matchesSearch = card.dataset.nama
        .toLowerCase()
        .includes(lowerSearch);
      const shouldShow = matchesCategory && matchesSearch;

      card.style.display = shouldShow ? "block" : "none";
      if (shouldShow) visibleCount++;
    });

    // Show empty state if no cards visible
    const emptyState = document.querySelector(".empty-state");
    const grid = document.getElementById("menuGrid");

    if (visibleCount === 0 && !emptyState) {
      const msg = document.createElement("p");
      msg.className = "empty-state";
      msg.textContent = search
        ? "Tidak ada menu yang cocok dengan pencarian"
        : "Tidak ada menu dalam kategori ini";
      grid.appendChild(msg);
    } else if (visibleCount > 0 && emptyState) {
      emptyState.remove();
    }
  }

  // ========== File Upload Preview (Optional Enhancement) ==========
  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        // Validate file size
        if (file.size > 5242880) {
          // 5MB
          alert("File terlalu besar! Maksimal 5MB");
          this.value = "";
          return;
        }

        // Validate file type
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        if (!allowedTypes.includes(file.type)) {
          alert(
            "Tipe file tidak valid! Hanya JPG, PNG, WEBP yang diperbolehkan"
          );
          this.value = "";
          return;
        }

        // Show file name
        const label = this.parentElement.querySelector("small");
        if (label) {
          label.textContent = `File dipilih: ${file.name} (${formatBytes(
            file.size
          )})`;
        }
      }
    });
  });

  // ========== Form Validation ==========
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function (e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';
      }
    });
  });

  // ========== Auto-hide Alerts ==========
  setTimeout(() => {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 500);
    });
  }, 5000);
});

// ========== Helper Functions ==========
function formatBytes(bytes, decimals = 2) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
}
