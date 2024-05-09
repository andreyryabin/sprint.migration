document.addEventListener("DOMContentLoaded", function (e) {

    let links = document.getElementsByClassName('sp-support-link');
    let contents = document.getElementsByClassName('sp-support-content');

    for (let index = 0; index < links.length; index++) {
        links[index].addEventListener('click', function () {
            sprint_open_tab(index);
        });
    }

    if (links.length > 0) {
        sprint_open_tab(0);
    }

    function sprint_open_tab(tabindex) {
        for (let index = 0; index < links.length; index++) {
            if (tabindex === index) {
                links[index].classList.add('active');
            } else {
                links[index].classList.remove('active');
            }
        }
        for (let index = 0; index < contents.length; index++) {
            if (tabindex === index) {
                contents[index].style.display = 'block';
            } else {
                contents[index].style.display = 'none';
            }
        }
    }
});
