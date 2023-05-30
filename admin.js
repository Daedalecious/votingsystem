document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".sidebar");
  const menuItems = sidebar.querySelectorAll("li");

  const sections = document.querySelectorAll(".content section");
  sections.forEach(function (section, index) {
    if (index !== 0) {
      section.style.display = "none";
    }
  });

  let activeMenuItem = localStorage.getItem("activeMenuItem");
  if (!activeMenuItem) {
    activeMenuItem = menuItems[0].querySelector("a").getAttribute("href");
    localStorage.setItem("activeMenuItem", activeMenuItem);
  }

  function showSection(sectionId) {
    sections.forEach(function (section) {
      section.style.display = "none";
    });

    const targetSection = document.querySelector(sectionId);
    if (targetSection) {
      targetSection.style.display = "block";
      targetSection.style.opacity = "0";
      setTimeout(function () {
        targetSection.style.opacity = "1";
      }, 10);
    }
  }

  function setActiveMenuItem(menuItem) {
    menuItems.forEach(function (item) {
      item.classList.remove("active");
    });
    menuItem.classList.add("active");
    activeMenuItem = menuItem.querySelector("a").getAttribute("href");
    localStorage.setItem("activeMenuItem", activeMenuItem);
  }

  function handleClick(e) {
    e.preventDefault();
    const sectionId = this.querySelector("a").getAttribute("href");
    showSection(sectionId);
    setActiveMenuItem(this);
  }

  menuItems.forEach(function (menuItem) {
    menuItem.addEventListener("click", handleClick);
  });

  const logoutMenuItem = sidebar.querySelector('a[href="?logout"]');
  logoutMenuItem.addEventListener("click", function (e) {
    e.preventDefault();
    window.location.href = this.href;
  });

  const activeMenuLink = sidebar.querySelector(`a[href="${activeMenuItem}"]`);
  if (activeMenuLink) {
    setActiveMenuItem(activeMenuLink.parentNode);
    showSection(activeMenuItem);
  }
});

function removeErrorMessage() {
  var errorMessages = document.getElementsByClassName('error-message');
  setTimeout(function () {
    Array.from(errorMessages).forEach(function (errorMessage) {
      errorMessage.style.display = 'none';
    });
  }, 2000);
}

window.addEventListener('load', removeErrorMessage);

const messageContainer = document.querySelector('.message-container');
if (messageContainer) {
  setTimeout(function () {
    messageContainer.style.opacity = '0';
    setTimeout(function () {
      messageContainer.remove();
    }, 1000);
  }, 2000);
}
