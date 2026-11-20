/**
 * SmartWarehouse Core Responsive & Interactive Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
    const sidebarWrapper = document.getElementById('sidebar-wrapper');
    const menuToggle = document.getElementById('menu-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    // 1. Sidebar Toggle Logic
    const toggleSidebar = (forceState) => {
        if (typeof forceState === 'boolean') {
            document.body.classList.toggle('sb-sidenav-toggled', forceState);
        } else {
            document.body.classList.toggle('sb-sidenav-toggled');
        }

        // On desktop, remember state in localStorage
        if (window.innerWidth >= 992) {
            const isToggled = document.body.classList.contains('sb-sidenav-toggled');
            try {
                localStorage.setItem('sw_sidebar_toggled', isToggled ? '1' : '0');
            } catch (e) {
                // Ignore localStorage errors
            }
        }
    };

    // Restore desktop preference on load
    if (window.innerWidth >= 992) {
        try {
            if (localStorage.getItem('sw_sidebar_toggled') === '1') {
                document.body.classList.add('sb-sidenav-toggled');
            }
        } catch (e) {}
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar(false);
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => {
            toggleSidebar(false);
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('sb-sidenav-toggled')) {
            toggleSidebar(false);
        }
    });

    // Close sidebar on mobile when navigation item is clicked
    if (sidebarWrapper) {
        const sidebarLinks = sidebarWrapper.querySelectorAll('a.list-group-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    toggleSidebar(false);
                }
            });
        });
    }

    // 2. Window Resize Handling: clean up state when crossing breakpoint
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth >= 992) {
                // If returning to desktop, restore desktop preference
                try {
                    const saved = localStorage.getItem('sw_sidebar_toggled') === '1';
                    document.body.classList.toggle('sb-sidenav-toggled', saved);
                } catch (e) {}
            } else {
                // On mobile default is closed
                document.body.classList.remove('sb-sidenav-toggled');
            }
        }, 150);
    });

    // 3. User-friendly Auto-dismiss for Flash Success Alerts (after 5s)
    const successAlerts = document.querySelectorAll('.alert-success.alert-dismissible');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            }
        }, 5000);
    });

    // 4. Enhanced Table Horizontal Scroll Indicators
    const tableResponsives = document.querySelectorAll('.table-responsive');
    tableResponsives.forEach(container => {
        const checkScroll = () => {
            const hasOverflow = container.scrollWidth > container.clientWidth;
            if (hasOverflow) {
                container.classList.add('has-horizontal-scroll');
            } else {
                container.classList.remove('has-horizontal-scroll');
            }
        };
        checkScroll();
        window.addEventListener('resize', checkScroll);
    });
});
