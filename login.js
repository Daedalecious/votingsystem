document.getElementById("loginForm").addEventListener("submit", function(event) {
  event.preventDefault();

  var number = document.getElementsByName("number")[0].value;
  var password = document.getElementsByName("password")[0].value;

  if (number.trim() === '' || password.trim() === '') {
    return;
  }

  var loginData = {
    number: number,
    password: password
  };

  fetch("index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(loginData)
  })
  .then(function(response) {
    return response.json();
  })
  .then(function(data) {
    console.log("Login response:", data);

    if (data.success) {
      if (data.role === "admin") {
        console.log("Redirecting to admin.php");
        window.location.href = "admin.php";
      } else {
        console.log("Redirecting to dashboard.php");
        window.location.href = "dashboard.php";
      }
    } else {
      console.log("Login failed:", data.message);
    }
  })
  .catch(function(error) {
    console.log("An error occurred during login:", error);
  });
});
