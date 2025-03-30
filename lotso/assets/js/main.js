document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const content = document.querySelector(".content");
    const navLinks = document.querySelectorAll(".nav-item a");

    // Xử lý sự kiện khi click vào menu
    navLinks.forEach(link => {
        link.addEventListener("click", function () {
            sidebar.classList.toggle("collapsed"); // Thu nhỏ/mở rộng sidebar
            content.classList.toggle("expanded"); // Điều chỉnh phần nội dung
        });
    });

    // Lưu trạng thái sidebar vào LocalStorage để duy trì khi chuyển trang
    if (localStorage.getItem("sidebarState") === "collapsed") {
        sidebar.classList.add("collapsed");
        content.classList.add("expanded");
    }

    // Khi sidebar thay đổi trạng thái, lưu lại vào LocalStorage
    sidebar.addEventListener("transitionend", function () {
        localStorage.setItem("sidebarState", sidebar.classList.contains("collapsed") ? "collapsed" : "expanded");
    });
});
