const fullname = document.getElementById('fullname');
const username = document.getElementById('username');
const phone = document.getElementById('phone');
const date = document.getElementById('date');
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');
const role = document.getElementById('role'); // Dropdown for user role
const submitButton = document.getElementById('btn');

const fullnameError = document.getElementById('fullnameError');
const usernameError = document.getElementById('usernameError');
const phoneError = document.getElementById('phoneError');
const dateError = document.getElementById('dateError');
const passError = document.getElementById('passError');
const confirmPassError = document.getElementById('confirmPassError');

function checkFullName() {
    if (!fullname.value.match(/^[a-zA-Z\s]+$/)) {
        fullnameError.textContent = "Name should only contain alphabets and spaces";
        return false;
    } else {
        fullnameError.textContent = "";
        return true;
    }
}
fullname.addEventListener('input', checkFullName);

function checkUsername() {
    if (!username.value.match(/^[a-zA-Z0-9._]+$/)) {
        usernameError.textContent = "Username can only contain letters, numbers, underscores (_) and periods (.)";
        return false;
    } else {
        usernameError.textContent = "";
        return true;
    }
}
username.addEventListener('input', checkUsername);

function checkPhone() { 
    if (!phone.value.match(/^07\d{8}$/)) {
        phoneError.textContent = "Enter a valid 10-digit phone number";
        return false;
    } else {
        phoneError.textContent = "";
        return true;
    }
}
phone.addEventListener('input', checkPhone);

// Date validation
function checkDate() {
    const inputDate = new Date(date.value);
    const today = new Date();
    
    // Calculate the minimum valid birthdate (16 years ago)
    const minValidDate = new Date();
    minValidDate.setFullYear(today.getFullYear() - 16);

    if (inputDate > today) {
        dateError.textContent = "Date of Birth cannot be in the future.";
        return false;
    } else if (inputDate > minValidDate) {
        dateError.textContent = "You must be at least 16 years old.";
        return false;
    } else {
        dateError.textContent = "";
        return true;
    }
}

date.addEventListener('input', checkDate);


// Password validation
function checkPassword() {
    if (!password.value.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/)) {
        passError.textContent = "Password must have 8+ characters, uppercase, lowercase, number & special character";
        return false;
    } else {
        passError.textContent = "";
        return true;
    }
}
password.addEventListener('input', checkPassword);

// Confirm Password validation
function checkConfirmPassword() {
    if (password.value !== confirmPassword.value) {
        confirmPassError.textContent = "Passwords do not match";
        return false;
    } else {
        confirmPassError.textContent = "";
        return true;
    }
}
confirmPassword.addEventListener('input', checkConfirmPassword);

function showSuccessDialog(usernameValue, roleValue) {
    alert(`You ${usernameValue} have successfully registered as a ${roleValue}.`);
}

// Form submission
function submitForm(event) {
    event.preventDefault();
    if (checkFullName() && checkUsername() && checkPhone() && checkDate() && checkPassword() && checkConfirmPassword()) {
        const usernameValue = username.value.trim();
        const roleValue = role.value; 
        showSuccessDialog(usernameValue, roleValue); 
        alert("Please correct the errors before submitting.");
    }
}
submitButton.addEventListener('click', submitForm);

function checkFormValidity() {
    if (checkFullName() && checkUsername() && checkPhone() && checkDate() && checkPassword() && checkConfirmPassword()) {
        submitButton.disabled = false;
    } else {
        submitButton.disabled = true;
    }
}

// Corrected event listeners
fullname.addEventListener('input', checkFormValidity);
username.addEventListener('input', checkFormValidity);
phone.addEventListener('input', checkFormValidity);
date.addEventListener('input', checkFormValidity);
password.addEventListener('input', checkFormValidity);
confirmPassword.addEventListener('input', checkFormValidity);

// Initially disable the button
submitButton.disabled = true;
