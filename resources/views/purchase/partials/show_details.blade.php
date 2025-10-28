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
    width: 100%;
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
</head>

<body>
  @php
  $purchase_lines_chunks = array_chunk($purchase->purchase_lines->toArray(),10);
  $total_pages = count($purchase_lines_chunks);
  $page_number = 1;
  if (!empty($purchase->contact->supplier_business_name)) {
  $supplier_business_name[] = $purchase->contact->supplier_business_name;
  }
  if (!empty($purchase->contact->address_line_1)) {
  $address_line_1[] = $purchase->contact->address_line_1;
  }
  if (!empty($purchase->contact->mobile)) {
  $mobile[] = $purchase->contact->mobile;
  }
  @endphp

  @foreach($purchase_lines_chunks as $chunk)
  <div class="modal-body">
    @if($purchase->type == 'purchase_order')
    <div><img class="watermark" src="{{$logowatermark}}" alt=""></div>
    @endif
    <div class="row invoice-info">
      <div class="header">
        <div class="sidehead2">
          @if(!empty($logo))
          <img src="{{$logo}}" alt="Logo">
          @endif
          <div class="title">
            @if($purchase->type == 'purchase')
            <h2>Bon de Reception</h2>
            @endif
          </div>
        </div>
        @if($purchase->type !== 'purchase_order')
        <div class="sidehaed1">
          <div class="details">
            <h3>SERVICE ACHATS</h3>
            <h4>Code: s1 - F6</h4>
            <h4>Version N°: 01</h4>
            <h4>Date d'édition: 23/12/2024</h4>
          </div>
          <div class="info-section">
            <div>Date: {{ @format_date($purchase->transaction_date) }}</div>
            <div>N° BON: #{{ $purchase->ref_no }}</div>
          </div>
        </div>
        @endif
        @if($purchase->type == 'purchase_order')
        <div class="sidehaed1">
          <p style=" font-size: 24px; font-weight: 700; ">FLURRY ICE</p>
          <p style=" font-weight: 600; ">SOCIÉTÉ  AU CAPITAL DE 100 000 DHS</p>
          <p>FABRICATION ET VENTE DE GLACES</p>
          <p>6, Avenue Abdellah Fakhar,
          Commune Azla - Tetouan (M)
            <svg width="20" height="25" viewBox="0 0 12 20" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <rect x="0.09375" width="15" height="25" fill="url(#pattern0_169_55)"></rect>
              <defs>
                <pattern id="pattern0_169_55" patternContentUnits="objectBoundingBox" width="1" height="1">
                  <use xlink:href="#image0_169_55" transform="matrix(0.0104167 0 0 0.00597042 0 0.21342)" />
                </pattern>
                <image id="image0_169_55" width="96" height="96" xlink:href="data:image/png;base64,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" />
              </defs>
            </svg>
          </p>
          <p>Tél.: 212-633395024
            <svg width="20" height="25" viewBox="0 0 12 20" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <rect x="0.09375" width="15" height="25" fill="url(#pattern0_169_58)"></rect>
              <defs>
                <pattern id="pattern0_169_58" patternContentUnits="objectBoundingBox" width="1" height="1">
                  <use xlink:href="#image0_169_58" transform="matrix(0.0104167 0 0 0.00642969 0 0.191375)" />
                </pattern>
                <image id="image0_169_58" width="96" height="96" xlink:href="data:image/png;base64,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" />
              </defs>
            </svg>
          </p>
          
          <p>E-mail: contact@flurryice.com
            <svg width="20" height="25" viewBox="0 0 12 20" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <rect x="0.09375" width="15" height="25" fill="url(#pattern0_169_57)"></rect>
              <defs>
                <pattern id="pattern0_169_57" patternContentUnits="objectBoundingBox" width="1" height="1">
                  <use xlink:href="#image0_169_57" transform="matrix(0.0104167 0 0 0.00597042 0 0.21342)" />
                </pattern>
                <image id="image0_169_57" width="96" height="96" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAYAAADimHc4AAAACXBIWXMAAAsTAAALEwEAmpwYAAADYUlEQVR4nO2cvWsUQRiHR/xA/AIJgoIWprFJo2msvOq4ufe3d7nC7YKlrf+Cpa3prAS7BCFFGu2sbNRCGxstbAKKCTkVET8SGbnAEUnudt7Zm92b3wMDKXKZmfeZ2Zl5Z3PGEEIIIYQQQgghhBBCCCGEGGMWFhYuAXgsIl8A7LBg3xi4GInIaqvVuhIy+BsMOooOvE0XO7UAN/IZfPjO+hW1AD52oHnk9kPMAD7z4R8DCkDcAUQBoICkH2Gm7DXAJA4oIC4UkLoAAA97vd6MSYxerzfj+l4FAa58EpFFkwgisuj6PE5s5ufnj6oqK7Lii8hTALNmSgEwO+jj2DHJ8/yUttJC2y4R+Q7gbp7nx8yU0Gg0jgC4IyJfK7cNPaC8sdZeNzXHWnsVwIvKngNGzIY/AB5Ya8+YmpFl2QkA90TktyYG6oZoKh8q6wBumprQbrczEfkQou9VEbBb1oJcUpREq9W6AOBRyD5XTYArfbeg5Xl+2FSHQwBulXHzp27ZqApE5LVPw0TkOYC5IOHT9W9u0BafPozse4gGHliBZosG4BeA+81m86SZMI1G47jbLgP4odlqRxew55DyxHOqvm+3200zIUTkhoi89Rz1z4bfeKiMgKHfz8c9puP/smKtPadu9P59Oeu2xQC2Pdq2CeC2Wy808fFpdOEKyuiolrIGRiUFlDHVfSn70VhpASEXO1OQ3c0BgG9lbg4qLyDEdg8F80pZll0D8NJz5r1yn590fCZVgebAsz0qr+RGrCJ/s+VzQKybgH+IyHnFkX/dLah7/2aWZR1F/sY7RVJLAYGSXmsuaMr8jTpJWGsBjm63exrA0iB17ZNX6hf93KCuJVe3UVJ7AaEuPmJdFE2NAIcyrxTlqnSqBOzS6XQuKw5PpR3qkhGwJ33wURH8jTLSGskIUOaVSk3sJSPAI6/0bpKp7WQEjJFX+hnjcicpAcN5JQDLIvLZFfdzrOvNJAVUCQqIDAVEhgIiQwGRoYDIUEBkKCAyFBAZCogMBUSGAiJDAZGhgMhQQGQoIHUBLKAAVHggcAaAAqKPQtR5BvCLW6ERsBVCwGrsUYT6lmW1APdO5eA/F2N3ZqdOxb0uY629aAJ+g/qKz7v4CZa+G/nBgk8IIYQQQgghhBBCCCGEEFN3/gJZV6uYzAYnkwAAAABJRU5ErkJggg==" />
              </defs>
            </svg>
          </p>
          @if($purchase->type == 'purchase_order')
          <h2>Bon de commande</h2>
          @endif
        </div>
        @endif
      </div>

      @if($purchase->type !== 'purchase_order')
      <div class="categories">
        <div>PMATÉRIEL DE PRODUCTION </div>
        <div>EMBALLAGE & CONDITIONNEMENT</div>
        <div>CONSOMMABLES & DIVERS</div>
      </div>
      @endif
      @if($purchase->type == 'purchase_order')
      <p>Les chiffres ci-après doivent figurer sur toutes les correspondances, bons de livraison, et factures relatives à la commande </p>
      <h5> B.C. N°: #{{ $purchase->ref_no }}</h5>
      <div class="categories">
        <div style=" width: 100%; ">
          <p>
            <a><b>À:</b></a>
            <a><b>Adresse de livraison:</b></a>
          </p>
          <p>
            <a>Entreprise: @if(!empty($supplier_business_name)){{ implode(', ', $supplier_business_name) }} @endif</a>
            <a>6, Avenue Abdellah Fakhar, Commune Azla - Tetouan</a>
          </p>
          <p>

            <a>Adresse: @if(!empty($address_line_1)){{ implode(', ', $address_line_1) }} @endif</a>

            <a>@lang('purchase.shipping_details'): {{ $purchase->shipping_details ?? '' }}</a>
          </p>
          <p>

            <a>Téléphone: @if(!empty($mobile)){{ implode(', ', $mobile) }} @endif</a>

            <a>Téléphone: 212-539689803</a>
          </p>

        </div>
      </div>
      @endif

      <div style="display: flex;flex-direction: column;justify-content: space-between;height: 560px;">
        <div class="table-section">
          <table style="border-collapse: separate; border-spacing: 0;">
            <thead>
              <tr>
                <th style="border-radius: 10px 0px 0px 0px;">#</th>
                <th>DESIGNATION DE LA MATIERE OU DE L'ARTICLE</th>
                @if($purchase->type == 'purchase')
                <th>FACT/BL</th>
                @endif
                <th>QTE</th>
                <th>UNITÉ</th>
                <th>P.U</th>
                <th style="border-radius: 0px 10px 0px 0px;">Total</th>
              </tr>
            </thead>
            <tbody>EMBALLAGE
              @foreach($chunk as $index => $purchase_line)
              <tr>
                <td lass="border border-gray-300 p-2">{{ $index + 1 }}</td>
                <td class="border border-gray-300 p-2">
                  @if($purchase_line['product']['type'] == 'variable')
                  {{ $purchase_line['variations']['name'] }}
                  @else
                  {{ $purchase_line['product']['name'] }}
                  @endif
                </td>
                @if($purchase->type == 'purchase')
                <td></td>
                @endif

                <td class="border border-gray-300 p-2">
                  <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_line['quantity'] }}</span>
                </td>
                <td class="border border-gray-300 p-2">
                  @if(!empty($purchase_line['sub_unit'])) {{$purchase_line['actual_name']}} @else {{$purchase_line['product']['unit']['actual_name']}} @endif
                </td>
                <td class="border border-gray-300 p-2"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line['pp_without_discount']}}</span></td>
                <td class="border border-gray-300 p-2"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line['purchase_price_inc_tax'] * $purchase_line['quantity'] }}</span></td>
              </tr>

              @endforeach
            @for($i = count($chunk); $i < 10; $i++)
                <tr>
                <td class="border border-gray-300 p-2">{{ $i + 1 }}</td>
                <td class="border border-gray-300 p-2"></td>
                <td class="border border-gray-300 p-2"></td>

              @if($purchase->type == 'purchase')
                <td class="border border-gray-300 p-2"></td>
              @endif
                <td class="border border-gray-300 p-2"></td>
                <td class="border border-gray-300 p-2"></td>
                <td class="border border-gray-300 p-2"></td>
                </tr>
            @endfor
              @if($purchase->type == 'purchase_order' && $page_number == $total_pages)
                <tr>
                  <td colspan="5" style="text-align: end;padding: 8px;font-weight: 600;">SOUS TOTAL: </td>
                  <td colspan="6" style="text-align: end;padding: 8px;font-weight: 600;">{{$purchase->total_before_tax/$purchase->exchange_rate}} MAD</td>
                </tr>
                <tr>
                  <td colspan="5" style="text-align: end;padding: 8px;font-weight: 600;">TOTAL: </td>
                  <td colspan="6" style="text-align: end;padding: 8px;font-weight: 600;">
                    {{ number_format($purchase->final_total, 2) }} MAD
                  </td>
                </tr>
                <tr>
                  <td colspan="6" style="text-align: end;padding: 8px;font-weight: 600;">
                    {!!ucfirst($total_in_words)!!} dirham
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>

        @if($purchase->type !== 'purchase_order')
        <div class="visa-section">
          <div>VISA MAGASINIER</div>
          <div>VISA SCE ACHATS</div>
          <div>VISA SCE PRODUCTION</div>
          <div>VISA SCE MAINTENANCE</div>
        </div>
        @endif
        <!-- ترقيم الصفحات -->
        <div class="page-number">
          <p>{{ $page_number }} / {{ $total_pages }}</p>
        </div>

        @php
        $page_number++;
        @endphp
      </div>
    </div>
  </div>
  @endforeach


</body>