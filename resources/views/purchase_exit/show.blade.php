<div class="modal-dialog modal-x" role="document" style="width: 50%;">
  <div class="modal-content" style="padding: 25px;">
    <style>
      body {
        font-family: Arial, sans-serif;
        margin: 20px;
      }

      .container {
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
        border: 1px solid #ddd;
        padding: 20px;
      }

      .sidehaed1 h2 {
        font-size: 34px;
        font-weight: 800;
        margin-top: 1.5rem;
      }

      .modal-body h5 {
        font-weight: 800;
        font-size: 16px;
      }

      .header,
      .visa-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
      }

      .header img {
        width: 150px;
        min-width: 100px;
      }

      .header .details {
        text-align: right;
        width: 100%;
        border-radius: 10px;
        padding: 10px;
        border: 1.5px solid #a5a5a5;
      }

      .details p {
        margin: 2px 0;
      }

      .title {
        text-align: start;
        margin: 20px 0;
      }

      .title h2 {
        font-size: 22px;
        font-weight: 800;
      }

      .sidehaed1 p {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin-bottom: -5px;
      }

      .info-section {
        display: flex;
        justify-content: space-between;
        margin: 20px 0;
        width: 100%;
      }

      .sidehaed1 {
        width: 65%;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
      }

      .info-section div {
        margin: 5px;
        width: 70%;
        border-radius: 5px;
        border: 1px solid #ddd;
        padding: 15px 15px;
        text-align: start;
      }

      .categories {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
      }

      .categories div {
        width: 32%;
        border: 1px solid #ddd;
        padding: 8px 10px;
        text-align: start;
        border-radius: 10px;
      }

      .categories p {
        display: flex;
        justify-content: flex-start;
        align-items: center;
      }

      .categories p a {
        width: 50%;
        color: #383838;
      }

      .table-section {
        margin: 20px 0;
      }

      .table-section table {
        width: 100%;
        border-collapse: collapse;
      }

      .table-section th,
      .table-section td {
        font-size: 12px;
        border: 1px solid #ddd;
        padding: 0px 5px;
        height: 25px;
        text-align: start;
      }

      .visa-section div {
        width: 24%;
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
        height: 60px;
      }

      .sidehead2 {
        width: 50%;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
      }

      .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.1;
        font-size: 4em;
        z-index: 1;
        width: 50%;
        background-size: contain;
        pointer-events: none;
      }
    </style>

    <body>
      @php
      // If $ingredients is an array, you can directly chunk it
      $purchase_lines_chunks = array_chunk($ingredients, 10);
      @endphp

      @foreach($purchase_lines_chunks as $chunk)
      <div class="modal-body">
        <div class="row invoice-info">
          <div class="header">
            <div class="sidehead2">
              @if(!empty($logo))
              <img src="{{$logo}}" alt="Logo">
              @endif
              <div class="title">
                <h2>Bon de Sortie</h2>
              </div>
            </div>
            <div class="sidehaed1">
              <div class="details">
                <h3>SERVICE ACHATS</h3>
                <h4>Code: s1 - F6</h4>
                <h4>Version N°: 01</h4>
                <h4>Date d'édition: 23/12/2024</h4>
              </div>
              <div class="info-section">
                <div>Date: {{ @format_date($production_purchase->transaction_date) }}</div>
                <div>N° BON: #{{ $production_purchase->ref_no }}</div>
              </div>
            </div>
          </div>

          <div class="categories">
            <div>PIECE DE RECHANGE</div>
            <div>EMBALLAGE</div>
            <div>FRITTES & AUTRES</div>
          </div>

          <div style="display: flex; flex-direction: column; justify-content: space-between; height: 560px;">
            <div class="table-section">
              <table style="border-collapse: separate; border-spacing: 0;">
                <thead>
                  <tr>
                    <th style="border-radius: 10px 0px 0px 0px;">#</th>
                    <th>DESIGNATION DE LA MATIERE OU DE L'ARTICLE</th>
                    <th>QTE</th>
                    <th>UNITÉ</th>
                    <th>DEMANNDEUR</th>
                    <th style="border-radius: 0px 10px 0px 0px;">TOTAL</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($chunk as $index => $purchase_exit)
                  <tr>
                    <td class="border border-gray-300 p-2">{{ $index + 1 }}</td>
                    <td class="border border-gray-300 p-2">
                      {{ $purchase_exit['full_name'] }}
                    </td>
                    <td>
                      @if($purchase_exit['quantity_exited'] == 0 && $purchase_exit['quantity'] != 0)
                      {{ @format_quantity($purchase_exit['quantity']) }}
                      @elseif($purchase_exit['quantity_exited'] != 0 && $purchase_exit['quantity'] == 0)
                      {{ @format_quantity($purchase_exit['quantity_exited']) }}
                      @elseif($purchase_exit['quantity_exited'] == 0 && $purchase_exit['quantity'] == 0)
                      -
                      @endif
                    </td>

                    <td class="border border-gray-300 p-2">{{$purchase_exit['unit']}}</td>
                    <td class="border border-gray-300 p-2"></td>
                    <td class="border border-gray-300 p-2"></td>
                  </tr>
                  @endforeach
                  @for($i = count($chunk); $i < 10; $i++)
                    <tr>
                    <td class="border border-gray-300 p-2">{{ $i + 1 }}</td>
                    <td class="border border-gray-300 p-2"></td>
                    <td class="border border-gray-300 p-2"></td>
                    <td class="border border-gray-300 p-2"></td>
                    <td class="border border-gray-300 p-2"></td>
                    <td class="border border-gray-300 p-2"></td>
                    </tr>
                    @endfor
                </tbody>
              </table>
            </div>
            <div class="visa-section">
              <div>VISA MAGASINIER</div>
              <div>VISA SCE ACHATS</div>
              <div>VISA SCE PRODUCTION</div>
              <div>VISA SCE MAINTENANCE</div>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </body>


    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" aria-label="Print"
        onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang('messages.print')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang('messages.close')</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    var element = $('div.modal-x');
    __currency_convert_recursively(element);
  });
</script>