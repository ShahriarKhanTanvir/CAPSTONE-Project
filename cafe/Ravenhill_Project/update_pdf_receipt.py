import os
import re

def update_index_php():
    with open('index.php', 'r', encoding='utf-8') as f:
        html = f.read()

    # Add html2canvas and jspdf to head if not present
    scripts = """  <!-- PDF Generation Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
"""
    if 'jspdf.umd.min.js' not in html:
        html = html.replace('</head>', scripts + '</head>')

    # Update receipt modal footer
    old_modal_footer = """      <div class="modal-footer">
        <button class="btn btn-secondary flex-1" id="print-receipt-btn"><i class="ri-printer-line"></i> Print Thermal Receipt</button>
        <button class="btn btn-primary" id="finish-receipt-btn">Done</button>
      </div>"""

    new_modal_footer = """      <div class="modal-footer" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn btn-secondary flex-1" id="print-receipt-btn" style="min-width: 130px;"><i class="ri-printer-line"></i> Print Receipt</button>
        <button class="btn btn-outline flex-1" id="download-pdf-receipt-btn" style="min-width: 140px; background: rgba(217, 107, 67, 0.15); border-color: var(--color-primary); color: #fff;"><i class="ri-file-pdf-line"></i> Download PDF</button>
        <button class="btn btn-primary" id="finish-receipt-btn" style="min-width: 80px;">Done</button>
      </div>"""

    html = html.replace(old_modal_footer, new_modal_footer)

    with open('index.php', 'w', encoding='utf-8') as f:
        f.write(html)
    print("Updated index.php for PDF receipt generation!")

def update_styles_css():
    with open('styles.css', 'r', encoding='utf-8') as f:
        css = f.read()

    new_print_css = """/* Print CSS for Thermal Receipt Printing & Print-Ready PDF (FR36) */
@media print {
  @page {
    margin: 0;
    size: 80mm auto;
  }
  html, body {
    background: #ffffff !important;
    color: #000000 !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }
  body * {
    visibility: hidden;
  }
  #receipt-modal, #receipt-modal *,
  #printable-receipt-modal, #printable-receipt-modal *,
  .thermal-receipt, .thermal-receipt * {
    visibility: visible;
  }
  #receipt-modal, #printable-receipt-modal {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: auto !important;
    background: #ffffff !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }
  .modal-backdrop {
    background: #ffffff !important;
    position: static !important;
    display: block !important;
  }
  .modal-card, .modal-content {
    background: #ffffff !important;
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 auto !important;
    max-width: 80mm !important;
    width: 80mm !important;
  }
  .modal-header, .modal-footer, .modal-close, button {
    display: none !important;
  }
  .thermal-receipt {
    box-shadow: none !important;
    border: none !important;
    padding: 8px 4px !important;
    width: 100% !important;
    max-width: 80mm !important;
    margin: 0 auto !important;
    color: #000000 !important;
    background: #ffffff !important;
    font-family: 'Courier New', Courier, monospace !important;
  }
  .thermal-receipt * {
    color: #000000 !important;
    background: transparent !important;
  }
}
"""

    pattern = re.compile(r"/\* Print CSS for Thermal Receipt Printing \(FR36\) \*/.*", re.DOTALL)
    if pattern.search(css):
        css = pattern.sub(new_print_css, css)
    else:
        css += "\n" + new_print_css

    with open('styles.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("Updated styles.css for print formatting!")

def update_app_js():
    with open('app.js', 'r', encoding='utf-8') as f:
        js = f.read()

    # Add generateReceiptPDF function
    pdf_func = """
// PDF Receipt Generator (FR36 - Download & Print-Ready PDF)
window.generateReceiptPDF = async function(shouldDownload = true) {
  const receiptEl = document.getElementById('thermal-receipt-content');
  if (!receiptEl) return;

  const orderId = document.getElementById('rec-order-id')?.textContent || 'Receipt';
  const cleanOrderId = orderId.replace(/[^a-zA-Z0-9_-]/g, '');

  try {
    if (window.html2canvas && window.jspdf) {
      const { jsPDF } = window.jspdf;
      
      // Temporarily set high contrast white background for clean rasterization
      const origBg = receiptEl.style.backgroundColor;
      const origColor = receiptEl.style.color;
      receiptEl.style.backgroundColor = '#ffffff';
      receiptEl.style.color = '#000000';

      const canvas = await html2canvas(receiptEl, {
        scale: 3, // 300 DPI equivalent for ultra-crisp vector-like thermal print
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
      });

      receiptEl.style.backgroundColor = origBg;
      receiptEl.style.color = origColor;

      const imgData = canvas.toDataURL('image/png');
      
      // 80mm POS thermal receipt format
      const imgWidth = 80;
      const pageHeight = (canvas.height * imgWidth) / canvas.width;
      
      const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: [imgWidth, Math.max(pageHeight + 4, 100)]
      });

      pdf.addImage(imgData, 'PNG', 0, 2, imgWidth, pageHeight);

      if (shouldDownload) {
        pdf.save(`Ravenhill_Receipt_${cleanOrderId}.pdf`);
      } else {
        // Direct print popup
        const blob = pdf.output('blob');
        const blobUrl = URL.createObjectURL(blob);
        const printIframe = document.createElement('iframe');
        printIframe.style.position = 'fixed';
        printIframe.style.right = '0';
        printIframe.style.bottom = '0';
        printIframe.style.width = '0';
        printIframe.style.height = '0';
        printIframe.style.border = '0';
        printIframe.src = blobUrl;
        document.body.appendChild(printIframe);
        printIframe.onload = () => {
          setTimeout(() => {
            printIframe.contentWindow.focus();
            printIframe.contentWindow.print();
          }, 300);
        };
      }
    } else {
      window.print();
    }
  } catch (err) {
    console.error('[PDF Receipt] Generation notice:', err);
    window.print();
  }
};
"""

    if 'window.generateReceiptPDF' not in js:
        js += pdf_func

    # Update receipt event handlers in completePaymentProcess
    old_handlers = """  // Set Receipt Action Handlers
  document.getElementById('print-receipt-btn').onclick = () => window.print();
  document.getElementById('finish-receipt-btn').onclick = () => {
    document.getElementById('receipt-modal').classList.add('hidden');
    // Sync all data after sale is complete
    syncBackendData();
  };
  document.getElementById('close-receipt-btn').onclick = () => {
    document.getElementById('receipt-modal').classList.add('hidden');
    syncBackendData();
  };"""

    new_handlers = """  // Set Receipt Action Handlers (PDF & Print Ready)
  const printBtn = document.getElementById('print-receipt-btn');
  if (printBtn) {
    printBtn.onclick = () => window.generateReceiptPDF(false);
  }
  const downloadPdfBtn = document.getElementById('download-pdf-receipt-btn');
  if (downloadPdfBtn) {
    downloadPdfBtn.onclick = () => window.generateReceiptPDF(true);
  }
  const finishBtn = document.getElementById('finish-receipt-btn');
  if (finishBtn) {
    finishBtn.onclick = () => {
      document.getElementById('receipt-modal').classList.add('hidden');
      syncBackendData();
    };
  }
  const closeRecBtn = document.getElementById('close-receipt-btn');
  if (closeRecBtn) {
    closeRecBtn.onclick = () => {
      document.getElementById('receipt-modal').classList.add('hidden');
      syncBackendData();
    };
  }"""

    js = js.replace(old_handlers, new_handlers)

    with open('app.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("Updated app.js with generateReceiptPDF engine!")

if __name__ == '__main__':
    update_index_php()
    update_styles_css()
    update_app_js()
