function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.src = '../img/eye-off.png'; // Χρειάζεται το εικονίδιο κλειστού ματιού
    } else {
        passwordInput.type = 'password';
        toggleIcon.src = '../img/eye.png'; // Χρειάζεται το εικονίδιο ανοιχτού ματιού
    }
}

const passwordInput = document.getElementById('password');
const passwordHintItems = document.querySelectorAll('.password-hint li');

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;
    
    // Έλεγχος κριτηρίων με πιο ευανάγνωστο τρόπο
    const criteria = [
        value.length >= 8 && value.length <= 16,
        /[A-Z]/.test(value),
        /\d/.test(value),
        /[!@#$%^&*(),.?":{}|<>]/.test(value)
    ];

    criteria.forEach((met, index) => {
        passwordHintItems[index].style.color = met ? '#4CAF50' : '#FF5252';
        passwordHintItems[index].style.opacity = met ? '1' : '0.7';
    });
});
