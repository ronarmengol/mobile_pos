const Modal = {
  init() {
    if (!document.getElementById("customModal")) {
      const modalHtml = `
                <div id="customModal" class="modal-overlay">
                    <div class="modal-content">
                        <h3 id="modalTitle" class="modal-title"></h3>
                        <p id="modalMessage" class="modal-message"></p>
                        <div id="modalActions" class="modal-actions"></div>
                    </div>
                </div>
            `;
      document.body.insertAdjacentHTML("beforeend", modalHtml);

      // Close on overlay click (optional, maybe not for confirm)
      document.getElementById("customModal").addEventListener("click", (e) => {
        if (e.target.id === "customModal") {
          // Modal.close(); // Uncomment if you want background click to close
        }
      });
    }
  },

  show({
    title = "Alert",
    message,
    type = "alert",
    onConfirm = null,
    confirmText = "OK",
    cancelText = "Cancel",
  }) {
    this.init();
    const modal = document.getElementById("customModal");
    const titleEl = document.getElementById("modalTitle");
    const msgEl = document.getElementById("modalMessage");
    const actionsEl = document.getElementById("modalActions");

    titleEl.textContent = title;
    msgEl.innerHTML = message;
    actionsEl.innerHTML = "";

    if (type === "confirm") {
      const cancelBtn = document.createElement("button");
      cancelBtn.className = "modal-btn modal-btn-cancel";
      cancelBtn.textContent = cancelText;
      cancelBtn.onclick = () => this.close();

      const confirmBtn = document.createElement("button");
      confirmBtn.className = "modal-btn modal-btn-confirm";
      confirmBtn.textContent = confirmText;
      confirmBtn.onclick = () => {
        this.close();
        if (onConfirm) onConfirm();
      };

      actionsEl.appendChild(cancelBtn);
      actionsEl.appendChild(confirmBtn);
    } else {
      const okBtn = document.createElement("button");
      okBtn.className = "modal-btn modal-btn-confirm";
      okBtn.textContent = confirmText;
      okBtn.onclick = () => {
        this.close();
        if (onConfirm) onConfirm();
      };
      actionsEl.appendChild(okBtn);
    }

    modal.classList.add("active");
  },

  close() {
    const modal = document.getElementById("customModal");
    if (modal) modal.classList.remove("active");
  },

  alert(message, title = "Notice") {
    this.show({ title, message, type: "alert" });
  },

  confirm(message, onConfirm, title = "Confirm Action") {
    this.show({ title, message, type: "confirm", onConfirm });
  },
};
