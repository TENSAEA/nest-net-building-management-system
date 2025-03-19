$(document).ready(function() {
    // Initialize datepickers
    $("#startDate").datepicker({
        dateFormat: 'mm/dd/yy' // Ensure the format matches your backend expectations
    });
    $("#endDate").datepicker({
        dateFormat: 'mm/dd/yy' // Ensure the format matches your backend expectations
    });

    // Handle report type change
    $("#reportType").change(function() {
        var reportType = $(this).val();
        if (reportType === "tenant" || reportType === "room") {
            $(".date-inputs").hide();
        } else {
            $(".date-inputs").show();
        }
    }).trigger('change'); // Trigger change event on page load to set initial state

    // Handle form submission
    $("#getReportForm").submit(function(event) {
        event.preventDefault();
        var fileType = $("#fileType").val();
        if (fileType === "pdf") {
            generatePDF();
        } else {
            this.submit();
        }
    });

    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        var reportType = $("#reportType").val();
        var startDate = $("#startDate").val();
        var endDate = $("#endDate").val();

        // Fetch data from server
        $.ajax({
            url: 'generate_report.php',
            type: 'POST',
            data: {
                reportType: reportType,
                startDate: startDate,
                endDate: endDate,
                fetchData: true
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Add title
                doc.text("Bayne Building Company", 10, 10);

                // Add report type
                doc.text("Report Type: " + reportType, 10, 20);

                // Add date range if applicable
                if (startDate && endDate) {
                    doc.text("Date Range: " + startDate + " to " + endDate, 10, 30);
                }

                // Add table headers and data
                var headers = [];
                var body = [];
                var total = 0;

                switch (reportType) {
                    case 'tenant':
                        headers = ['No', 'Full Name', 'Company Name', 'Floor', 'Room', 'Mobile No', 'Rent Amount', 'Status'];
                        data.forEach((item, index) => {
                            body.push([index + 1, item.full_name, item.company_name, item.floor, item.room, item.mobile_no, item.rent_amount,  item.status]);
                        });
                        break;

                    case 'payment':
                        headers = ['No', 'Tenant Name', 'TIN No', 'Room', 'Paid Month', 'FS Number', 'Payment Date', 'Z No', 'Total'];
                        data.forEach((item, index) => {
                            body.push([index + 1, item.tenant_name, item.tenant_tin, item.room, item.paid_months, item.fs_number, item.payment_date, item.total]);
                            total += parseFloat(item.total);
                        });
                        body.push(['', '', '', '', '', '', '', 'Total', total.toFixed(2)]);
                        break;

                    case 'additional_fee':
                        headers = ['No', 'Description', 'Registration Date',  'Price'];
                        data.forEach((item, index) => {
                            body.push([index + 1, item.description, item.registration_date, item.price]);
                            total += parseFloat(item.price);
                        });
                        body.push(['', '', '', 'Total Amount', total.toFixed(2)]);
                        break;

                    case 'expense':
                        headers = ['No', 'Date', 'Expense Type', 'Total'];
                        data.forEach((item, index) => {
                            body.push([index + 1, item.date, item.expense_type, item.cost]);
                            total += parseFloat(item.cost);
                        });
                        body.push(['', '', '', 'Total Cost', total.toFixed(2)]);
                        break;

                    case 'room':
                        headers = ['No', 'Room', 'Floor', 'Area', 'Tenant', 'Monthly Price', 'Status'];
                        data.forEach((item, index) => {
                            body.push([index + 1, item.room, item.floor, item.area, item.tenant, item.monthly_price, item.status]);
                        });
                        break;
                }

                // Add table to PDF
                doc.autoTable({
                    head: [headers],
                    body: body,
                    startY: 40,
                    styles: { 
                        cellPadding: 3, 
                        fontSize: 10, 
                        halign: 'center', 
                        valign: 'middle', 
                        lineWidth: 0.1, 
                        lineColor: [0, 0, 0], 
                        textColor: [0, 0, 0] // Set text color to black
                    },
                    headStyles: { 
                        fillColor: [255, 255, 255], 
                        textColor: [0, 0, 0], 
                        fontStyle: 'bold' 
                    },
                    footStyles: { 
                        fillColor: [255, 255, 255], 
                        textColor: [0, 0, 0], 
                        fontStyle: 'bold' 
                    }
                });

                // Save the PDF
                doc.save('report.pdf');
            },
            error: function(xhr, status, error) {
                alert('Error fetching data: ' + error);
            }
        });
    }
});