/**
 * Composable for exporting table data to CSV (Excel) and PDF.
 * @param columns Array<{ key: string, label: string }>
 * @param title Document title for PDF
 */

export function useTableExport(columns, title = 'Export') {
  function escapeCsv(val) {
    if (val == null) return ''
    const s = String(val)
    if (s.includes(',') || s.includes('"') || s.includes('\n')) {
      return '"' + s.replace(/"/g, '""') + '"'
    }
    return s
  }

  function exportToExcel(data, filename = 'export') {
    const headers = columns.map((c) => c.label)
    const rows = data.map((row) => columns.map((c) => escapeCsv(row[c.key] ?? '')))
    const csv = [headers.join(','), ...rows.map((r) => r.join(','))].join('\r\n')
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${filename}.csv`
    a.click()
    URL.revokeObjectURL(url)
  }

  /**
   * @param data Table rows
   * @param filename Download filename (no extension)
   * @param statistics Optional Array<{ label: string, value: string }> shown above the table
   */
  function exportToPdf(data, filename = 'export', statistics = []) {
    import('jspdf').then(({ jsPDF }) => {
      import('jspdf-autotable').then(({ default: autoTable }) => {
        const doc = new jsPDF({ orientation: 'landscape' })
        let y = 12
        doc.setFontSize(14)
        doc.text(title, 14, y)
        y += 8

        if (statistics && statistics.length > 0) {
          doc.setFontSize(10)
          doc.setTextColor(80, 80, 80)
          statistics.forEach(({ label, value }) => {
            doc.text(`${label}: ${value}`, 14, y)
            y += 6
          })
          doc.setTextColor(0, 0, 0)
          y += 4
        }

        const headers = columns.map((c) => c.label)
        const rows = data.map((row) => columns.map((c) => (row[c.key] != null ? String(row[c.key]) : '')))
        autoTable(doc, {
          head: [headers],
          body: rows,
          startY: y,
          styles: { fontSize: 8 }
        })
        doc.save(`${filename}.pdf`)
      })
    })
  }

  return { exportToExcel, exportToPdf }
}
