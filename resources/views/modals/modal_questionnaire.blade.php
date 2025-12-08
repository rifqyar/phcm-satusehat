<!-- Modal Respon Kuesioner -->
<div class="modal fade" id="questionnaireModal" tabindex="-1" role="dialog" aria-labelledby="questionnaireModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionnaireModalLabel">Respon Kuesioner</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="questionnaireForm">
                    <div id="questionsContainer">
                        <!-- Questions will be loaded here -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveResponse()">Simpan Respon</button>
            </div>
        </div>
    </div>
</div>
