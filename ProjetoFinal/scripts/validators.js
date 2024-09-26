
    // Exemplo de filtros para username e password
    const usernamefilter = /^[a-zA-Z0-9_]{3,16}$/;
    const passwordfilter = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;


function GuestUserAndPasswordValidator(theForm) {

    // Verifica se o botão Login foi clicado
    if (theForm.login === "Login") {
        return true;
    }

    // se ambos vazios
    if (theForm.username.value === "") {
        alert("Username missing...");
        return false;
    }

    if (theForm.password.value === "") {
        alert("Password missing...");
        return false;
    }


    // respeitando o filtro de username
    if (!usernamefilter.test(theForm.username.value)) {
        alert('Please provide a valid username');
        theForm.username.focus();
        return false;
    }

    // respeitando o filtro de password
    if (!passwordfilter.test(theForm.password.value)) {
        alert('Please provide a valid password');
        theForm.password.focus();
        return false;
    }

    return true;
}