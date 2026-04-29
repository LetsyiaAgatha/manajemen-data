const ROLES = {
    ADMIN: {
        name: "Administrator",
        menus: ['dashboard', 'approval', 'explorer', 'rujukan', 'settings']
    },
    LOKET: {
        name: "Loket Pendaftaran",
        menus: ['dashboard', 'approval'] // Only input and entry queue
    },
    VERIFIKATOR: {
        name: "Verifikator (BPJS)",
        menus: ['dashboard', 'approval', 'explorer']
    },
    PERAWAT: {
        name: "Perawat Poli",
        menus: ['dashboard', 'explorer', 'rujukan']
    },
    DOKTER: {
        name: "Dokter Spesialis",
        menus: ['dashboard', 'explorer']
    }
};

// Export to window for access across files
window.APP_ROLES = ROLES;
