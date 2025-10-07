async function ajaxGetJson(url, onsuccess, onerror) {
    $.ajax(url, {
        type: "get",
        dataType: "json",
        beforeSend: function () {
            Swal.fire({
                html: "<h5>Please Wait...</h5>",
                showConfirmButton: false,
                allowOutsideClick: false,
            });

            Swal.showLoading();
        },
        success: function (data, status, xhr) {
            // success callback function
            Swal.close();
            window[onsuccess](data);
        },
        error: function (jqXhr, textStatus, errorMessage) {
            // error callback
            Swal.close();
            let text =
                jqXhr.responseJSON?.message == undefined
                    ? "Terjadi Kesalahan Pada Sistem!"
                    : jqXhr.responseJSON.message;

            console.log(text);
            $.toast({
                heading: "Gagal Mengambil Data",
                text: text,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "error",
                hideAfter: 3500,
            });
            // window[onerror](errorMessage);
        },
    });
}

async function ajaxPostJson(url, form, onsuccess) {
    $.ajax(url, {
        type: "post",
        dataType: "json",
        data: form,
        beforeSend: function () {
            Swal.fire({
                html: "<h5>Please Wait...</h5>",
                showConfirmButton: false,
                allowOutsideClick: false,
            });

            Swal.showLoading();
        },
        success: function (data, status, xhr) {
            // success callback function
            Swal.close();
            window[onsuccess](data);
        },
        error: function (jqXhr, textStatus, errorMessage) {
            // error callback
            Swal.close();

            let text =
                jqXhr.responseJSON?.message == undefined
                    ? "Terjadi Kesalahan Pada Sistem!"
                    : jqXhr.responseJSON.message;

            $.toast({
                heading: "Gagal Menginput Data",
                text: text,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "error",
                hideAfter: 3500,
            });
        },
    });
}

async function ajaxPostFile(url, form, onsuccess) {
    $.ajax(url, {
        type: "post",
        data: form,
        processData: false,
        contentType: false,
        beforeSend: function () {
            Swal.fire({
                html: "<h5>Please Wait...</h5>",
                showConfirmButton: false,
                allowOutsideClick: false,
            });

            Swal.showLoading();
        },
        success: function (data, status, xhr) {
            // success callback function
            // Swal.close()
            window[onsuccess](data);
        },
        error: function (jqXhr, textStatus, errorMessage) {
            // error callback
            Swal.close();

            let text =
                jqXhr.responseJSON?.message == undefined
                    ? "Terjadi Kesalahan Pada Sistem!"
                    : jqXhr.responseJSON.message;

            $.toast({
                heading: "Gagal Memproses Data",
                text: text,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "error",
                hideAfter: 3500,
            });
            // window[onerror](jqXhr);
        },
    });
}

function sAlert(title, msg, type) {
    Swal.fire({
        title: title,
        timer: 1500,
        text: msg,
        type: type,
        timerProgressBar: true,
        showConfirmButton: false,
    });
}
