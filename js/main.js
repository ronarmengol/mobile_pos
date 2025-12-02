let cart = [];

function renderProducts() {
  const grid = document.getElementById("productGrid");
  grid.innerHTML = "";

  products.forEach((p) => {
    const el = document.createElement("div");
    el.className = "product-card";
    el.onclick = () => addToCart(p);
    el.innerHTML = `
            <div class="product-name">${p.name}</div>
            <div class="product-price">K${parseFloat(p.price).toFixed(2)}</div>
        `;
    grid.appendChild(el);
  });
}

function addToCart(product) {
  const existing = cart.find((item) => item.id === product.id);
  if (existing) {
    existing.qty++;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      price: parseFloat(product.price),
      qty: 1,
    });
  }
  renderCart();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  renderCart();
}

function renderCart() {
  const container = document.getElementById("cartItems");
  const totalEl = document.getElementById("cartTotal");

  if (cart.length === 0) {
    container.innerHTML =
      '<div style="text-align: center; color: #999; padding: 20px;">Cart is empty</div>';
    totalEl.innerText = "K0.00";
    return;
  }

  container.innerHTML = "";
  let total = 0;

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.qty;
    total += itemTotal;

    const el = document.createElement("div");
    el.className = "cart-item";
    el.innerHTML = `
            <div style="flex: 2;">${item.name} x${item.qty}</div>
            <div style="flex: 1; text-align: right;">K${itemTotal.toFixed(
              2
            )}</div>
            <div style="width: 30px; text-align: center; color: red; cursor: pointer;" onclick="removeFromCart(${index})">×</div>
        `;
    container.appendChild(el);
  });

  totalEl.innerText = "K" + total.toFixed(2);
}

function completeSale() {
  if (cart.length === 0) {
    Modal.alert("Please add items to the cart first.", "Cart is Empty");
    return;
  }

  const totalAmount = document.getElementById("cartTotal").innerText;

  Modal.confirm(
    "Confirm payment of " + totalAmount + "?",
    () => {
      document.getElementById("cartData").value = JSON.stringify(cart);
      document.getElementById("finalTotal").value = cart.reduce(
        (sum, item) => sum + item.price * item.qty,
        0
      );

      // Create a hidden input to signal form submission
      const form = document.getElementById("saleForm");
      const hiddenInput = document.createElement("input");
      hiddenInput.type = "hidden";
      hiddenInput.name = "complete_sale";
      hiddenInput.value = "1";
      form.appendChild(hiddenInput);

      form.submit();
    },
    "Confirm Payment"
  );
}

// Initialize
document.addEventListener("DOMContentLoaded", renderProducts);
