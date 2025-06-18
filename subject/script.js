// auto remove for alert Message 
        setTimeout(() => {
            const alertBox = document.querySelector('.alert');
            if (alertBox) alertBox.remove();

            // Remove `?msg=...` from URL
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.pathname);
        }, 3000);
