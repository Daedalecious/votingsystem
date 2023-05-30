
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const menuItems = sidebar.querySelectorAll("li");
  
    const sections = document.querySelectorAll(".content section");
    sections.forEach(function (section, index) {
      if (index !== 0) {
        section.style.display = "none";
      }
    });
  
    menuItems.forEach(function (menuItem) {
      menuItem.addEventListener("click", function (e) {
        e.preventDefault();
  
        const sectionId = this.querySelector("a").getAttribute("href");
  
        sections.forEach(function (section) {
          section.style.display = "none";
        });
  
        const targetSection = document.querySelector(sectionId);
        if (targetSection) {
          targetSection.style.display = "block";
        }
      });
    });
  
    const winnersMenuItem = sidebar.querySelector('a[href="#winners"]');
    winnersMenuItem.addEventListener("click", function (e) {
      e.preventDefault();
  
      sections.forEach(function (section) {
        section.style.display = "none";
      });
  
      const winnersSection = document.querySelector("#winners");
      if (winnersSection) {
        winnersSection.style.display = "block";
      }
    });
  
    const logoutMenuItem = sidebar.querySelector('a[href="?logout"]');
    logoutMenuItem.addEventListener("click", function (e) {
      e.preventDefault();
  
      window.location.href = this.href;
    });
  });
  document.addEventListener("DOMContentLoaded", function() {
    var voteButton = document.querySelector(".votebttn");
    var message = document.createElement("div");
    message.className = "message success";
    
    voteButton.addEventListener("click", function() {
      message.innerText = "Vote recorded successfully!";
      document.body.appendChild(message);

      setTimeout(function() {
        message.style.opacity = "0";
      }, 2000);

      setTimeout(function() {
        message.remove();
      }, 4000);

      setTimeout(function() {
        location.reload();
      }, 2000);
    });
  });