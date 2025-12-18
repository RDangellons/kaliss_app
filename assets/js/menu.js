const btnMenu = document.getElementById("btnMenu");
const nav = document.getElementById("nav");

if (btnMenu && nav) {
  btnMenu.addEventListener("click", () => {
    nav.classList.toggle("is-open");
  });
}
