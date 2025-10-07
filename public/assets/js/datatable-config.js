$.extend(true, $.fn.dataTable.defaults, {
    language: {
        // processing: '<div class="spinner-border custom-spinner" role="status"><span class="visually-hidden custom-text"></span></div>',
        lengthMenu: "Menampilkan _MENU_ entri",
        info: "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
        infoEmpty: "Tidak ada entri yang tersedia",
        infoFiltered: "(disaring dari _MAX_ total entri)",
        search: "Cari:",
        paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
        }
    }
});