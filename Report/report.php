<?php require_once 'includes/header.php'; ?>
<?php require_once 'db_connect.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-body">
                <form class="form-horizontal" action="generate_report.php" method="post" id="getReportForm">
                    <div class="form-group">
                        <label for="reportType" class="col-sm-2 control-label">Report Type</label>
                        <div class="col-sm-10">
                            <select class="form-control" id="reportType" name="reportType">
                                <option value="tenant">Tenant Report</option>
                                <option value="payment">Payment Report</option>
                                <option value="additional_fee">Additional Fee Report</option>
                                <option value="expense">Expense Report</option>
                                <option value="room">Room Report</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group date-inputs">
                        <label for="startDate" class="col-sm-2 control-label">Start Date</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="startDate" name="startDate" placeholder="Start Date" />
                        </div>
                    </div>
                    <div class="form-group date-inputs">
                        <label for="endDate" class="col-sm-2 control-label">End Date</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="endDate" name="endDate" placeholder="End Date" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fileType" class="col-sm-2 control-label">File Type</label>
                        <div class="col-sm-10">
                            <select class="form-control" id="fileType" name="fileType">
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-success" id="generateReportBtn">
                                <i class="glyphicon glyphicon-ok-sign"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include jsPDF and autoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
<script src="custom/js/report.js"></script>
<?php require_once 'includes/footer.php'; ?>