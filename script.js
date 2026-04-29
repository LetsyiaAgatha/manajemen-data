document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuToggle) menuToggle.addEventListener('click', toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
    }

    // ROLE MANAGEMENT LOGIC
    const activeRole = localStorage.getItem('activeRole') || 'ADMIN';
    const roles = window.APP_ROLES;
    
    if (roles && roles[activeRole]) {
        applyRoleAccess(activeRole);
    }

    function applyRoleAccess(roleKey) {
        const role = roles[roleKey];
        const allowedMenus = role.menus;
        
        // Update Profile UI
        const nameEl = document.querySelector('.user-name');
        const roleEl = document.querySelector('.user-role');
        const profileImg = document.querySelector('.user-profile img');
        
        if (roleEl) roleEl.textContent = role.name;
        if (profileImg) {
             profileImg.src = `https://ui-avatars.com/api/?name=${role.name.replace(/\s+/g, '+')}&background=6C5CE7&color=fff&rounded=true`;
        }

        // Filter Sidebar Menus
        const menuItems = document.querySelectorAll('.sidebar-menu li');
        menuItems.forEach(li => {
            const link = li.querySelector('a');
            if (link) {
                const href = link.getAttribute('href');
                let menuKey = '';
                if (href.includes('index')) menuKey = 'dashboard';
                else if (href.includes('approval')) menuKey = 'approval';
                else if (href.includes('explorer')) menuKey = 'explorer';
                else if (href.includes('rujukan')) menuKey = 'rujukan';
                else if (href.includes('settings')) menuKey = 'settings';
                else if (href.includes('profile')) menuKey = 'profile';

                if (menuKey && !allowedMenus.includes(menuKey) && menuKey !== 'profile' && menuKey !== 'logout') {
                    li.style.display = 'none';
                } else {
                    li.style.display = 'block';
                }
            }
        });
    }

    // Prototype Role Switcher - Click profile to switch
    const profileArea = document.querySelector('.user-profile');
    if (profileArea) {
        profileArea.style.position = 'relative';
        profileArea.addEventListener('click', (e) => {
            const existing = document.getElementById('roleSwitcher');
            if (existing) {
                existing.remove();
                return;
            }
            
            const switcher = document.createElement('div');
            switcher.id = 'roleSwitcher';
            switcher.style.cssText = "position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border-radius: 12px; width: 220px; z-index: 1001; padding: 12px; margin-top: 10px;";
            
            const title = document.createElement('div');
            title.textContent = "Switch Prototype Role:";
            title.style.cssText = "font-size: 11px; font-weight: 700; color: #999; margin-bottom: 8px; text-transform: uppercase;";
            switcher.appendChild(title);

            Object.keys(roles).forEach(key => {
                const btn = document.createElement('button');
                btn.textContent = roles[key].name;
                btn.style.cssText = "width: 100%; text-align: left; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 4px; transition: 0.2s;";
                btn.onmouseover = () => { btn.style.background = "#6C5CE7"; btn.style.color = "#fff"; };
                btn.onmouseout = () => { btn.style.background = "transparent"; btn.style.color = "inherit"; };
                btn.onclick = (event) => {
                    event.stopPropagation();
                    localStorage.setItem('activeRole', key);
                    location.reload();
                };
                switcher.appendChild(btn);
            });
            profileArea.appendChild(switcher);
        });
    }

    // Global Search Fix for Explorer
    const searchInputs = document.querySelectorAll('.search-wrapper input');
    searchInputs.forEach(searchInput => {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.file-table tbody tr, .data-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
});
