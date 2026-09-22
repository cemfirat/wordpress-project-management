(function () {
    'use strict';

    function addClass(elements, className) {
        elements.forEach(function (element) {
            className.split(' ').forEach(function (name) {
                if (name) element.classList.add(name);
            });
        });
    }

    function enhanceAcf(root) {
        root = root || document;

        addClass(root.querySelectorAll('.acf-form input[type="text"], .acf-form input[type="email"], .acf-form input[type="url"], .acf-form input[type="number"], .acf-form input[type="tel"], .acf-form input[type="password"], .acf-form input[type="search"], .acf-form input[type="date"], .acf-form input[type="time"]'), 'uk-input');
        addClass(root.querySelectorAll('.acf-form textarea'), 'uk-textarea');
        addClass(root.querySelectorAll('.acf-form select:not(.select2-hidden-accessible)'), 'uk-select');
        addClass(root.querySelectorAll('.acf-form .acf-button, .acf-form input[type="submit"]'), 'uk-button uk-button-primary');
        addClass(root.querySelectorAll('.acf-form .acf-field'), 'uk-margin');
        addClass(root.querySelectorAll('.acf-form .acf-label label'), 'uk-form-label');
        addClass(root.querySelectorAll('.acf-form .acf-input'), 'uk-form-controls');
        addClass(root.querySelectorAll('.acf-form .acf-repeater .acf-button'), 'uk-button-small');
        addClass(root.querySelectorAll('.acf-form .acf-gallery .acf-button, .acf-form .acf-file-uploader .acf-button, .acf-form .acf-image-uploader .acf-button'), 'uk-button-default');

        if (window.UIkit && typeof window.UIkit.update === 'function') {
            window.UIkit.update(root);
        }
    }

    function handleConfirm(event) {
        var target = event.target.closest('[data-ambra-confirm]');
        if (!target || target.dataset.ambraConfirmed === '1') return;

        event.preventDefault();
        var message = target.getAttribute('data-ambra-confirm') || 'Wirklich ausführen?';

        function proceed() {
            target.dataset.ambraConfirmed = '1';
            if (target.tagName === 'A') {
                window.location.href = target.href;
            } else if (target.form) {
                target.form.requestSubmit ? target.form.requestSubmit() : target.form.submit();
            }
        }

        if (window.UIkit && window.UIkit.modal && typeof window.UIkit.modal.confirm === 'function') {
            window.UIkit.modal.confirm(message, {i18n: {ok: 'Bestätigen', cancel: 'Abbrechen'}}).then(proceed, function () {});
        } else if (window.confirm(message)) {
            proceed();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceAcf(document);
        document.addEventListener('click', handleConfirm);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) enhanceAcf(node);
                });
            });
        });
        observer.observe(document.body, {childList: true, subtree: true});
    });

    if (window.acf && typeof window.acf.addAction === 'function') {
        window.acf.addAction('append', function ($el) {
            enhanceAcf($el && $el[0] ? $el[0] : document);
        });
    }
}());
