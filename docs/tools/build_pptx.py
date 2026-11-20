# -*- coding: utf-8 -*-
"""
Generator presentasi PowerPoint untuk Proyek Akhir SmartWarehouse AI.

Dipakai:
    python tools/build_pptx.py

Menghasilkan: PRESENTASI_PROYEK_AKHIR.pptx (16:9, 28 slide, dengan catatan pembicara)
"""
import os
import re

from lxml import etree
from PIL import Image
from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import MSO_ANCHOR, PP_ALIGN
from pptx.oxml.ns import qn
from pptx.util import Emu, Inches, Pt

# ------------------------------------------------------------------ palet
NAVY = RGBColor(0x0B, 0x1F, 0x33)
NAVY_2 = RGBColor(0x13, 0x2C, 0x45)
TEAL = RGBColor(0x0F, 0x76, 0x6E)
TEAL_LIGHT = RGBColor(0xE6, 0xF2, 0xF0)
INK = RGBColor(0x1B, 0x24, 0x2E)
GREY = RGBColor(0x53, 0x5C, 0x68)
GREY_LIGHT = RGBColor(0xF4, 0xF6, 0xF9)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
LINE = RGBColor(0xDD, 0xE3, 0xEA)
GOLD = RGBColor(0xB4, 0x86, 0x2E)

HEAD = "Segoe UI Semibold"
BODY = "Segoe UI"
MONO = "Consolas"

SW, SH = 13.333, 7.5
ML = 0.62
CW = SW - 2 * ML

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS = os.path.join(BASE, "assets")
TOTAL = 28


# ------------------------------------------------------------------ util
def strip_style(shape):
    """Hapus <p:style> agar efek/shadow tema tidak ikut tampil."""
    el = shape._element
    style = el.find(qn("p:style"))
    if style is not None:
        el.remove(style)


def fill(shape, color):
    shape.fill.solid()
    shape.fill.fore_color.rgb = color
    shape.line.fill.background()
    strip_style(shape)


def bg(slide, color):
    slide.background.fill.solid()
    slide.background.fill.fore_color.rgb = color


def add_runs(p, text, size, color, bold=False, italic=False, font=BODY):
    """Dukung **bold**, *italic*, `mono`."""
    for part in re.split(r"(\*\*.+?\*\*|`[^`]+`|\*[^*\n]+?\*)", text):
        if not part:
            continue
        if part.startswith("**") and part.endswith("**") and len(part) > 4:
            r = p.add_run()
            r.text = part[2:-2]
            r.font.bold = True
        elif part.startswith("`") and part.endswith("`") and len(part) > 2:
            r = p.add_run()
            r.text = part[1:-1]
            r.font.name = MONO
            r.font.size = Pt(size - 1.5)
        elif part.startswith("*") and part.endswith("*") and len(part) > 2:
            r = p.add_run()
            r.text = part[1:-1]
            r.font.italic = True
        else:
            r = p.add_run()
            r.text = part
        r.font.name = font
        r.font.color.rgb = color
        r.font.italic = r.font.italic or italic
        if r.font.size is None:
            r.font.size = Pt(size)
        if bold:
            r.font.bold = True
    return p


def textbox(slide, x, y, w, h, text, size=16, bold=False, color=INK,
            align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP, italic=False,
            font=BODY, spacing=1.0):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.word_wrap = True
    tf.margin_left = 0
    tf.margin_right = 0
    tf.margin_top = 0
    tf.margin_bottom = 0
    tf.vertical_anchor = anchor
    p = tf.paragraphs[0]
    p.alignment = align
    p.line_spacing = spacing
    add_runs(p, text, size, color, bold=bold, italic=italic, font=font)
    return box


def bullet_para(p, level, char=None):
    pPr = p._p.get_or_add_pPr()
    pPr.set("marL", str(int(Inches(0.30 + 0.34 * level))))
    pPr.set("indent", str(int(-Inches(0.24))))
    buFont = etree.SubElement(pPr, qn("a:buFont"))
    buFont.set("typeface", "Arial")
    buChar = etree.SubElement(pPr, qn("a:buChar"))
    buChar.set("char", char or ("•" if level == 0 else "–"))


def bullets(slide, items, x, y, w, h, size=16.5, gap=7, color=INK,
            line=1.12, char=None):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.word_wrap = True
    tf.margin_left = 0
    tf.margin_right = 0
    tf.margin_top = 0
    tf.margin_bottom = 0
    first = True
    for item in items:
        level, text = item[0], item[1]
        p = tf.paragraphs[0] if first else tf.add_paragraph()
        first = False
        p.space_after = Pt(gap)
        p.line_spacing = line
        bullet_para(p, level, char)
        add_runs(p, text, size - (1.5 if level else 0), color)
    return box


def rect(slide, x, y, w, h, color, shape=MSO_SHAPE.RECTANGLE, radius=None):
    s = slide.shapes.add_shape(shape, Inches(x), Inches(y), Inches(w), Inches(h))
    fill(s, color)
    s.shadow.inherit = False
    if radius is not None and shape == MSO_SHAPE.ROUNDED_RECTANGLE:
        s.adjustments[0] = radius
    return s


def header(slide, kicker, title, size=29):
    rect(slide, ML, 0.44, 0.10, 0.52, TEAL)
    textbox(slide, ML + 0.26, 0.34, CW - 0.3, 0.26, kicker.upper(), size=10.5,
            bold=True, color=TEAL)
    textbox(slide, ML + 0.26, 0.58, CW - 0.3, 0.62, title, size=size,
            bold=True, color=NAVY, font=HEAD)
    ln = slide.shapes.add_connector(1, Inches(ML), Inches(1.38),
                                    Inches(SW - ML), Inches(1.38))
    ln.line.color.rgb = LINE
    ln.line.width = Pt(0.75)
    strip_style(ln)
    ln.shadow.inherit = False


def footer(slide, num, label="SmartWarehouse AI — Proyek Akhir RPL"):
    textbox(slide, ML, 7.03, 8.0, 0.25, label, size=9, color=GREY)
    textbox(slide, SW - ML - 1.2, 7.03, 1.2, 0.25, "%02d / %d" % (num, TOTAL),
            size=9, color=GREY, align=PP_ALIGN.RIGHT)


def notes(slide, text):
    slide.notes_slide.notes_text_frame.text = text


def new(prs, dark=False):
    s = prs.slides.add_slide(prs.slide_layouts[6])
    if dark:
        bg(s, NAVY)
    return s


# ------------------------------------------------------------------ komponen
def card(slide, x, y, w, h, title, body, num=None, fill_color=GREY_LIGHT,
         title_color=NAVY, body_color=GREY, accent=TEAL, title_size=14,
         body_size=12.5):
    box = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x),
                                 Inches(y), Inches(w), Inches(h))
    fill(box, fill_color)
    box.adjustments[0] = 0.08
    box.shadow.inherit = False
    rect(slide, x, y, 0.055, h, accent)
    if num:
        textbox(slide, x + 0.22, y + 0.14, w - 0.4, 0.24, num, size=10.5,
                bold=True, color=accent)
        textbox(slide, x + 0.22, y + 0.40, w - 0.4, h - 0.55, title,
                size=title_size, bold=True, color=title_color, font=HEAD,
                spacing=0.95)
        if body:
            textbox(slide, x + 0.22, y + 0.40 + 0.34 * (1 + title.count("\n")),
                    w - 0.44, h - 0.8, body, size=body_size, color=body_color)
    else:
        textbox(slide, x + 0.22, y + 0.16, w - 0.44, 0.3, title,
                size=title_size, bold=True, color=title_color, font=HEAD)
        if body:
            textbox(slide, x + 0.22, y + 0.16 + 0.42, w - 0.44, h - 0.62,
                    body, size=body_size, color=body_color, spacing=1.05)


def metric(slide, x, y, w, h, value, label, note="", color=TEAL):
    box = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x),
                                 Inches(y), Inches(w), Inches(h))
    fill(box, WHITE)
    box.line.color.rgb = LINE
    box.line.width = Pt(1)
    box.adjustments[0] = 0.10
    box.shadow.inherit = False
    textbox(slide, x + 0.16, y + 0.14, w - 0.32, 0.72, value, size=34,
            bold=True, color=color, font=HEAD)
    textbox(slide, x + 0.16, y + 0.86, w - 0.32, 0.5, label, size=11.5,
            bold=True, color=NAVY)
    if note:
        textbox(slide, x + 0.16, y + h - 0.5, w - 0.32, 0.4, note, size=9.5,
                color=GREY)


def figure(slide, path, x, y, w, h, caption=None, border=True):
    full = path if os.path.isabs(path) else os.path.join(ASSETS, path)
    iw, ih = Image.open(full).size
    ratio = iw / ih
    box_ratio = w / h
    if box_ratio > ratio:
        ph = h
        pw = h * ratio
    else:
        pw = w
        ph = w / ratio
    px = x + (w - pw) / 2
    py = y + (h - ph) / 2
    if border:
        frame = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(px - 0.06),
                                       Inches(py - 0.06), Inches(pw + 0.12),
                                       Inches(ph + 0.12))
        frame.fill.background()
        frame.line.color.rgb = LINE
        frame.line.width = Pt(1)
        strip_style(frame)
        frame.shadow.inherit = False
    slide.shapes.add_picture(full, Inches(px), Inches(py), Inches(pw), Inches(ph))
    if caption:
        textbox(slide, x, y + h + 0.06, w, 0.3, caption, size=10, color=GREY,
                align=PP_ALIGN.CENTER, italic=True)


def table(slide, data, x, y, w, col_ratios, row_h=0.34, head_h=0.40,
          size=11, head_size=11):
    rows, cols = len(data), len(data[0])
    shape = slide.shapes.add_table(rows, cols, Inches(x), Inches(y),
                                   Inches(w), Inches(head_h + row_h * (rows - 1)))
    tbl = shape.table
    tbl.first_row = True
    total = sum(col_ratios)
    for i, r in enumerate(col_ratios):
        tbl.columns[i].width = Emu(int(Inches(w) * r / total))
    tbl.rows[0].height = Inches(head_h)
    for i in range(1, rows):
        tbl.rows[i].height = Inches(row_h)
    for ri, row in enumerate(data):
        for ci, val in enumerate(row):
            cell = tbl.cell(ri, ci)
            cell.margin_left = Inches(0.08)
            cell.margin_right = Inches(0.08)
            cell.margin_top = Inches(0.03)
            cell.margin_bottom = Inches(0.03)
            cell.vertical_anchor = MSO_ANCHOR.MIDDLE
            cell.fill.solid()
            if ri == 0:
                cell.fill.fore_color.rgb = NAVY
            else:
                cell.fill.fore_color.rgb = WHITE if ri % 2 else GREY_LIGHT
            tf = cell.text_frame
            tf.word_wrap = True
            p = tf.paragraphs[0]
            p.alignment = PP_ALIGN.LEFT
            add_runs(p, val, head_size if ri == 0 else size,
                     WHITE if ri == 0 else INK, bold=(ri == 0))
    return tbl


def chip(slide, x, y, w, h, text, color=TEAL_LIGHT, text_color=TEAL,
         size=11.5, bold=True):
    s = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x),
                               Inches(y), Inches(w), Inches(h))
    fill(s, color)
    s.adjustments[0] = 0.35
    s.shadow.inherit = False
    tf = s.text_frame
    tf.margin_left = Inches(0.06)
    tf.margin_right = Inches(0.06)
    tf.margin_top = 0
    tf.margin_bottom = 0
    tf.vertical_anchor = MSO_ANCHOR.MIDDLE
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    add_runs(p, text, size, text_color, bold=bold)
    return s


def arrow(slide, x, y, w=0.26, h=0.20, color=None):
    s = slide.shapes.add_shape(MSO_SHAPE.RIGHT_ARROW, Inches(x), Inches(y),
                               Inches(w), Inches(h))
    fill(s, color or TEAL)
    s.shadow.inherit = False
    return s


# ------------------------------------------------------------------ slide
def slide_title(prs, n):
    s = new(prs, dark=True)
    rect(s, 0, 0, SW, 0.16, TEAL)
    rect(s, ML, 1.55, 1.1, 0.05, TEAL)
    textbox(s, ML, 1.75, CW, 0.3,
            "PROYEK AKHIR MANDIRI · REKAYASA PERANGKAT LUNAK (TIKB2503021)",
            size=12, bold=True, color=RGBColor(0x8F, 0xC9, 0xC2))
    textbox(s, ML, 2.15, CW, 0.95, "SmartWarehouse AI", size=52, bold=True,
            color=WHITE, font=HEAD)
    textbox(s, ML, 3.12, 10.6, 0.95,
            "Sistem Informasi Manajemen Pergudangan Cerdas Berbasis Web "
            "Terintegrasi Google Gemini AI dan WhatsApp Notification Gateway",
            size=17, color=RGBColor(0xC9, 0xD6, 0xE2), spacing=1.15)
    rect(s, ML, 4.35, 3.2, 0.02, RGBColor(0x2C, 0x4A, 0x63))
    textbox(s, ML, 4.55, 6.4, 1.4,
            "**Disusun oleh**\n[Nama Lengkap Mahasiswa] · [NIM]\n"
            "Kelas [Kelas] · Kelompok [Nomor]",
            size=12.5, color=RGBColor(0xC9, 0xD6, 0xE2), spacing=1.35)
    textbox(s, 7.35, 4.55, 5.4, 1.4,
            "**Dosen Pengampu**\nYudhistira Arie Wijaya, M.Kom., CDS.\n"
            "S1 Teknik Informatika · STMIK IKMI Cirebon · 2025/2026",
            size=12.5, color=RGBColor(0xC9, 0xD6, 0xE2), spacing=1.35)
    textbox(s, ML, 6.55, CW, 0.3,
            "github.com/[username]/proyek-rpl-smartwarehouse", size=11,
            color=RGBColor(0x7F, 0x9A, 0xB3))
    notes(s, "[0:00–0:30] Assalamualaikum. Saya [Nama], NIM [NIM], kelas "
             "[Kelas]. Presentasi ini membahas SmartWarehouse AI, aplikasi "
             "pergudangan berbasis web yang saya bangun dari analisis "
             "kebutuhan sampai pipeline CI/CD. Saya akan menunjukkan "
             "rancangan, demo langsung, dan bukti pengujiannya.")


def slide_agenda(prs, n):
    s = new(prs)
    header(s, "Alur presentasi", "Empat tahap, satu aplikasi yang berjalan")
    data = [
        ("01", "Analisis Kebutuhan", "PRD/SRS · 28 FR · 14 NFR · 12 user story",
         "± 3 menit"),
        ("02", "Perancangan Sistem", "4 diagram UML · ERD 12 tabel · prototipe UI",
         "± 4 menit"),
        ("03", "Implementasi & AI", "69 berkas · 16 intent · guardrail · HITL",
         "± 3 menit"),
        ("04", "Pengujian & Demo", "39 test PASS · CI/CD hijau · demo langsung",
         "± 5 menit"),
    ]
    y = 1.72
    for code, title, sub, dur in data:
        rect(s, ML, y, CW, 1.12, GREY_LIGHT)
        rect(s, ML, y, 0.055, 1.12, TEAL)
        textbox(s, ML + 0.28, y + 0.20, 0.8, 0.6, code, size=26, bold=True,
                color=TEAL, font=HEAD)
        textbox(s, ML + 1.25, y + 0.22, 6.6, 0.35, title, size=17, bold=True,
                color=NAVY, font=HEAD)
        textbox(s, ML + 1.25, y + 0.62, 8.2, 0.3, sub, size=12, color=GREY)
        textbox(s, SW - ML - 1.6, y + 0.36, 1.4, 0.3, dur, size=12,
                color=TEAL, bold=True, align=PP_ALIGN.RIGHT)
        y += 1.24
    textbox(s, ML, 6.68, CW, 0.3,
            "Setiap klaim pada presentasi ini memiliki bukti: kode, berkas uji, "
            "atau riwayat pipeline.", size=10.5, color=GREY, italic=True)
    footer(s, n)
    notes(s, "[0:30–1:00] Presentasi mengikuti empat tahap panduan. Tahap 1 "
             "analisis, tahap 2 perancangan, tahap 3 implementasi dan "
             "pengujian, tahap 4 hasil. Demo aplikasi saya lakukan di bagian "
             "akhir agar waktu penjelasan tidak terpotong.")


def slide_divider(prs, n, num, title, sub, points):
    s = new(prs, dark=True)
    textbox(s, 8.9, 1.55, 4.2, 2.6, num, size=150, bold=True,
            color=RGBColor(0x1B, 0x38, 0x52), align=PP_ALIGN.RIGHT, font=HEAD)
    rect(s, ML, 2.35, 1.0, 0.05, TEAL)
    textbox(s, ML, 2.55, 8.4, 0.4, "TAHAP %d" % int(num), size=12, bold=True,
            color=RGBColor(0x8F, 0xC9, 0xC2))
    textbox(s, ML, 2.95, 8.6, 0.9, title, size=38, bold=True, color=WHITE,
            font=HEAD)
    textbox(s, ML, 3.95, 8.2, 0.6, sub, size=15,
            color=RGBColor(0xC9, 0xD6, 0xE2), spacing=1.2)
    y = 4.85
    for p in points:
        rect(s, ML, y + 0.10, 0.09, 0.09, TEAL)
        textbox(s, ML + 0.26, y, 8.4, 0.32, p, size=13,
                color=RGBColor(0xDD, 0xE6, 0xEF))
        y += 0.44
    textbox(s, SW - ML - 1.2, 7.03, 1.2, 0.25, "%02d / %d" % (n, TOTAL),
            size=9, color=RGBColor(0x6E, 0x86, 0x9E), align=PP_ALIGN.RIGHT)
    return s


def slide_problems(prs, n):
    s = new(prs)
    header(s, "Tahap 1 · Latar belakang", "Enam masalah nyata di gudang kecil–menengah")
    textbox(s, ML, 1.55, CW, 0.3,
            "Objek observasi: gudang dengan 5–8 staf, 200–800 SKU, pencatatan "
            "masih manual atau semi-spreadsheet.", size=12, color=GREY, italic=True)
    items = [
        ("M-01", "Stok tercatat terpencar", "Buku, spreadsheet, dan ingatan staf "
         "menyimpan angka berbeda."),
        ("M-02", "Data antar-dokumen tidak sinkron", "Satu transaksi tercatat di "
         "dua berkas dengan nilai berbeda."),
        ("M-03", "Posisi barang tidak terdokumentasi", "Staf mencari manual di "
         "puluhan rak."),
        ("M-04", "Tanpa peringatan dini", "Stok kritis baru diketahui saat barang "
         "habis."),
        ("M-05", "Stock opname lambat", "Penghitungan ulang memakan waktu dan "
         "rawan selisih."),
        ("M-06", "Riwayat mutasi tidak dapat diaudit", "Tidak ada jejak siapa "
         "mengubah apa dan kapan."),
    ]
    x0, y0 = ML, 1.98
    w, h, gx, gy = 3.86, 1.72, 0.24, 0.22
    for i, (code, title, body) in enumerate(items):
        x = x0 + (i % 3) * (w + gx)
        y = y0 + (i // 3) * (h + gy)
        card(s, x, y, w, h, title, body, num=code)
    rect(s, ML, 5.86, CW, 0.72, TEAL_LIGHT)
    rect(s, ML, 5.86, 0.06, 0.72, TEAL)
    textbox(s, ML + 0.28, 5.98, CW - 0.5, 0.5,
            "**Akar masalahnya satu:** tidak ada sumber data tunggal yang "
            "dipercaya semua orang — sehingga enam gejala di atas muncul "
            "sekaligus.", size=12.5, color=NAVY)
    footer(s, n)
    notes(s, "[1:00–1:45] Latar belakang: pencatatan manual. Enam masalah ini "
             "saya beri kode M-01 sampai M-06, dan seluruh kebutuhan pada tahap "
             "berikutnya dilacak kembali ke kode masalah ini. Perhatikan M-01 "
             "dan M-02: sumber masalahnya sama, yaitu tidak ada satu sumber "
             "kebenaran data.")


def slide_solution(prs, n):
    s = new(prs)
    header(s, "Tahap 1 · Solusi", "Satu sumber kebenaran data, tiga nilai pembeda")
    textbox(s, ML, 1.58, 5.5, 1.1,
            "**SmartWarehouse AI** menggantikan pencatatan manual dengan basis "
            "data relasional tunggal, lalu menambahkan antarmuka bahasa alami "
            "di atasnya.", size=14, color=INK, spacing=1.2)
    textbox(s, ML, 2.75, 5.5, 0.3, "Tujuan produk (TP-01 – TP-05)", size=11,
            bold=True, color=TEAL)
    bullets(s, [
        (0, "Seluruh mutasi tercatat pada `transaksi_detail` (100%)."),
        (0, "Respons halaman laporan < 2 detik pada 1.000 baris."),
        (0, "Notifikasi stok kritis terkirim < 10 detik."),
        (0, "≥ 90% pertanyaan uji AI dikenali dengan benar."),
        (0, "Seluruh berkas PHP lolos `php -l` dan 39 test PASS."),
    ], ML, 3.08, 5.5, 2.3, size=12.5, gap=5)
    rect(s, ML, 5.05, 5.5, 1.30, TEAL_LIGHT)
    textbox(s, ML + 0.22, 5.18, 5.05, 0.3, "YANG DINILAI DARI TAHAP INI",
            size=9.5, bold=True, color=TEAL)
    textbox(s, ML + 0.22, 5.46, 5.05, 0.8,
            "PRD/SRS (Bab II) · matriks pelacakan · spesifikasi fitur AI "
            "(bagian 2.8) — bobot 20% rubrik proyek.", size=10.5, color=NAVY,
            spacing=1.15)
    x = 6.55
    w = 6.14
    card(s, x, 1.58, w, 1.55, "Antarmuka bahasa alami",
         "Pengguna bertanya \"berapa stok bearing di rak A.1.05\" tanpa "
         "membuka menu laporan.", num="NILAI 1")
    card(s, x, 3.25, w, 1.55, "Keamanan berlapis pada lapisan AI",
         "Setiap perintah yang mengubah data wajib dikonfirmasi pengguna "
         "(human-in-the-loop).", num="NILAI 2")
    card(s, x, 4.92, w, 1.55, "Notifikasi WhatsApp otomatis",
         "Peringatan stok kritis dan bukti mutasi terkirim ke nomor pengelola.",
         num="NILAI 3")
    footer(s, n)
    notes(s, "[1:45–2:30] Solusinya bukan sekadar menambah fitur chat. Ada tiga "
             "nilai pembeda: bahasa alami, keamanan berlapis, dan notifikasi. "
             "Tujuan produk saya nyatakan sebagai metrik yang bisa diukur, "
             "bukan slogan.")


def slide_scope(prs, n):
    s = new(prs)
    header(s, "Tahap 1 · Ruang lingkup", "Batas yang jelas agar kualitas terkendali")
    rect(s, ML, 1.62, 5.9, 3.55, GREY_LIGHT)
    textbox(s, ML + 0.25, 1.78, 5.4, 0.3, "TERMASUK DALAM RUANG LINGKUP",
            size=11, bold=True, color=TEAL)
    bullets(s, [
        (0, "Autentikasi dan otorisasi tiga peran (RBAC)."),
        (0, "Master data barang, kategori, lokasi rak, pengguna."),
        (0, "Transaksi masuk, keluar, dan pindah antar-rak."),
        (0, "Penyesuaian stok (stock opname) beralasan."),
        (0, "Dashboard, lima laporan, dan audit trail."),
        (0, "Asisten AI: tanya-jawab, eksekusi terkonfirmasi, query baca."),
        (0, "Notifikasi WhatsApp dan notifikasi dalam aplikasi."),
        (0, "Pengujian otomatis dan pipeline CI/CD."),
    ], ML + 0.25, 2.12, 5.4, 2.9, size=12.5, gap=5)
    rect(s, 6.82, 1.62, 5.9, 2.05, WHITE)
    rect(s, 6.82, 1.62, 5.9, 2.05, WHITE).line.color.rgb = LINE
    textbox(s, 7.07, 1.78, 5.4, 0.3, "DI LUAR RUANG LINGKUP", size=11,
            bold=True, color=GOLD)
    bullets(s, [
        (0, "Aplikasi mobile native (Android/iOS)."),
        (0, "Pemindai barcode/RFID dan integrasi IoT."),
        (0, "Akuntansi keuangan dan faktur pajak."),
        (0, "Multi-gudang / multi-tenant."),
    ], 7.07, 2.12, 5.4, 1.4, size=12.5, gap=5)
    textbox(s, 6.82, 3.92, 5.9, 0.3, "TIGA PERAN DENGAN PEWARISAN HAK",
            size=11, bold=True, color=TEAL)
    chip(s, 6.82, 4.28, 1.75, 0.42, "Admin")
    textbox(s, 8.62, 4.34, 0.3, 0.3, "⊳", size=14, color=GREY)
    chip(s, 8.92, 4.28, 1.85, 0.42, "Supervisor", color=RGBColor(0xD8, 0xEC, 0xEA))
    textbox(s, 10.82, 4.34, 0.3, 0.3, "⊳", size=14, color=GREY)
    chip(s, 11.12, 4.28, 1.60, 0.42, "Operator", color=RGBColor(0xC9, 0xE4, 0xE1))
    textbox(s, 6.82, 4.90, 5.9, 0.9,
            "Admin mewarisi seluruh hak Supervisor; Supervisor mewarisi hak "
            "Operator. Karena itu use case umum cukup dihubungkan ke Operator.",
            size=11.5, color=GREY, italic=True, spacing=1.15)
    rect(s, ML, 5.55, CW, 0.78, TEAL_LIGHT)
    rect(s, ML, 5.55, 0.06, 0.78, TEAL)
    textbox(s, ML + 0.28, 5.68, CW - 0.55, 0.55,
            "**Keputusan lingkup:** satu gudang, satu organisasi, antarmuka "
            "web — kedalaman fitur diutamakan daripada cakupan platform.",
            size=12.5, color=NAVY)
    footer(s, n)
    notes(s, "[2:30–3:10] Ruang lingkup penting disebut agar penilai tahu "
             "batasnya. Saya juga menetapkan yang di luar lingkup, misalnya "
             "barcode dan multi-gudang, supaya pengerjaan tidak melebar. "
             "Pewarisan hak akses menyederhanakan diagram use case.")


def slide_prd(prs, n):
    s = new(prs)
    header(s, "Tahap 1 · PRD/SRS", "Kebutuhan lengkap, berkode, dan dapat dilacak")
    metric(s, ML, 1.62, 2.86, 1.55, "28", "Kebutuhan fungsional",
           "FR-01 … FR-28", TEAL)
    metric(s, ML + 3.05, 1.62, 2.86, 1.55, "14", "Kebutuhan non-fungsional",
           "NFR-01 … NFR-14", TEAL)
    metric(s, ML + 6.10, 1.62, 2.86, 1.55, "12", "User story",
           "dengan acceptance criteria", TEAL)
    metric(s, ML + 9.15, 1.62, 2.94, 1.55, "100%", "Terlacak",
           "FR → use case → modul → test", NAVY)
    textbox(s, ML, 3.42, 6.1, 0.3, "CONTOH KEBUTUHAN FUNGSIONAL", size=11,
            bold=True, color=TEAL)
    rows = [
        ["Kode", "Kebutuhan", "Prioritas"],
        ["FR-08", "Catat barang masuk beserta supplier dan rak tujuan", "Must"],
        ["FR-18", "Asisten AI mengembalikan respons terstruktur (intent + data)", "Must"],
        ["FR-21", "Eksekusi query baca hanya SELECT/SHOW, maksimal 100 baris", "Must"],
        ["FR-28", "Seluruh POST diverifikasi token CSRF oleh router", "Must"],
    ]
    table(s, rows, ML, 3.74, 6.1, [0.8, 4.0, 0.9], row_h=0.46, size=11)
    textbox(s, 6.95, 3.42, 5.8, 0.3, "STRUKTUR DOKUMEN", size=11, bold=True,
            color=TEAL)
    bullets(s, [
        (0, "Ruang lingkup, pemangku kepentingan, dan tujuan terukur."),
        (0, "Asumsi, ketergantungan, dan batasan teknis."),
        (0, "Kebutuhan fungsional + non-fungsional berkode."),
        (0, "User story dan acceptance criteria yang dapat diuji."),
        (0, "Matriks pelacakan kebutuhan (Tabel 2.4)."),
        (0, "Spesifikasi kebutuhan integrasi AI (bagian 2.8)."),
    ], 6.95, 3.74, 5.8, 2.4, size=12.5, gap=6)
    textbox(s, ML, 6.62, CW, 0.3,
            "Acuan penulisan: IEEE 830 / ISO 29148 sebagaimana disyaratkan "
            "panduan proyek.", size=10.5, color=GREY, italic=True)
    footer(s, n)
    notes(s, "[3:10–3:50] Hasil tahap 1: 28 kebutuhan fungsional, 14 "
             "non-fungsional, 12 user story. Setiap kebutuhan punya kode, "
             "prioritas, dan modul pelaksana. Empat contoh di kiri adalah yang "
             "paling sering ditanyakan: transaksi, respons AI, query baca, dan "
             "CSRF.")


def slide_architecture(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · Arsitektur", "Arsitektur berlapis dengan pola MVC")
    figure(s, "fig_architecture.png", ML, 1.62, 6.9, 4.95,
           "Gambar 3.1 Arsitektur Berlapis Sistem SmartWarehouse AI")
    x = 7.75
    layers = [
        ("Presentation", "View Bootstrap 5.3, form, tabel, grafik Chart.js."),
        ("Application", "Router, autentikasi, otorisasi peran, CSRF, validasi."),
        ("Domain / Service", "10 model, 5 helper, WarehouseAiEngine."),
        ("Data", "PDO + prepared statement ke MySQL 8.0, 12 tabel."),
    ]
    y = 1.66
    for name, desc in layers:
        rect(s, x, y, 4.95, 1.06, GREY_LIGHT)
        rect(s, x, y, 0.05, 1.06, TEAL)
        textbox(s, x + 0.22, y + 0.14, 4.5, 0.3, name, size=13.5, bold=True,
                color=NAVY, font=HEAD)
        textbox(s, x + 0.22, y + 0.48, 4.5, 0.5, desc, size=11, color=GREY,
                spacing=1.05)
        y += 1.18
    textbox(s, x, 6.35, 4.95, 0.5,
            "Tanpa framework: routing, keamanan, dan akses data ditulis "
            "eksplisit agar mekanismenya dipahami.", size=10.5, color=GREY,
            italic=True)
    footer(s, n)
    notes(s, "[3:50–4:20] Sebelum diagram, ini kerangka besarnya. Empat "
             "lapisan: presentasi, aplikasi, domain, dan data. Saya sengaja "
             "tidak memakai framework supaya mekanisme routing dan keamanan "
             "ditulis sendiri. Setiap diagram UML berikut memotret sudut "
             "pandang berbeda dari arsitektur yang sama.")


def slide_usecase(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · UML 1", "Use Case Diagram: 12 use case, 3 aktor internal")
    figure(s, "fig_usecase.png", ML, 1.58, 6.5, 5.15,
           "Gambar 3.2 Use Case Diagram SmartWarehouse AI")
    x = 7.35
    bullets(s, [
        (0, "**12 use case** dalam satu batas sistem: autentikasi, dashboard, "
            "transaksi, stok, laporan, AI, notifikasi."),
        (0, "**Generalisasi aktor:** Admin ⊳ Supervisor ⊳ Operator, sehingga "
            "use case umum cukup terhubung ke Operator."),
        (0, "**«include»** dari UC-03…UC-06 ke UC-12: setiap mutasi selalu "
            "memicu notifikasi WhatsApp."),
        (0, "**Dua aktor eksternal:** Google Gemini (pemahaman bahasa) dan "
            "WhatsApp Gateway (pengiriman pesan)."),
        (0, "Setiap use case dijabarkan menjadi skenario pada Tabel 3.1."),
    ], x, 1.70, 5.45, 4.6, size=13.5, gap=9)
    chip(s, x, 5.62, 1.35, 0.40, "UC-01…UC-12", color=TEAL_LIGHT, size=10.5)
    chip(s, x + 1.47, 5.62, 1.75, 0.40, "3 aktor internal", color=TEAL_LIGHT,
         size=10.5)
    chip(s, x + 3.34, 5.62, 2.0, 0.40, "2 aktor eksternal", color=TEAL_LIGHT,
         size=10.5)
    footer(s, n)
    notes(s, "[4:20–5:10] Diagram use case. Perhatikan panah generalisasi di "
             "kiri: Admin mewarisi hak Supervisor, Supervisor mewarisi hak "
             "Operator. Konsekuensinya, use case umum cukup dihubungkan ke "
             "Operator. Di kanan, UC-12 notifikasi di-include oleh empat use "
             "case transaksi.")


def slide_activity(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · UML 2", "Activity Diagram: alur bisnis dan alur AI")
    figure(s, "fig_activity_business.png", ML, 1.60, 5.85, 4.55,
           "Gambar 3.3 Alur Bisnis Barang Masuk")
    figure(s, "fig_activity_ai.png", 6.75, 1.60, 5.85, 4.55,
           "Gambar 3.4 Pemrosesan Pesan Asisten AI")
    textbox(s, ML, 6.42, 5.85, 0.55,
            "Validasi dilakukan **sebelum** transaksi basis data dibuka; "
            "setelah COMMIT, garis fork menjalankan notifikasi dan bukti "
            "transaksi secara paralel.", size=10.5, color=GREY, spacing=1.1)
    textbox(s, 6.75, 6.42, 5.85, 0.55,
            "Lima titik keputusan: di luar domain, minta SQL langsung, JSON "
            "tidak valid, intent tidak dikenali, dan aksi butuh konfirmasi.",
            size=10.5, color=GREY, spacing=1.1)
    footer(s, n)
    notes(s, "[5:10–6:00] Dua activity diagram. Yang kiri alur barang masuk: "
             "validasi dulu, baru BEGIN, lalu commit, lalu fork untuk "
             "notifikasi. Yang kanan alur AI dengan lima titik keputusan. "
             "Setiap cabang 'tidak' punya penanganan sendiri, tidak berhenti "
             "dengan error.")


def slide_class(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · UML 3", "Class Diagram: tiga lapisan kelas")
    figure(s, "fig_class.png", ML, 1.60, 6.75, 4.95,
           "Gambar 3.5 Class Diagram Sistem")
    x = 7.60
    bullets(s, [
        (0, "**Baris atas — controller:** Router meneruskan permintaan; "
            "controller tidak menyimpan logika data."),
        (0, "**Baris tengah — model:** seluruh akses basis data melalui PDO "
            "dan prepared statement."),
        (0, "**Baris bawah — helper/service:** WarehouseAiEngine, "
            "WhatsAppHelper, NotificationHelper, AuthHelper, SecurityHelper."),
        (0, "**Garis putus-putus** = dependency: kelas di atas memakai kelas "
            "di bawah, bukan mewarisinya."),
        (0, "**«singleton» Database** menjamin satu koneksi PDO per siklus "
            "permintaan."),
    ], x, 1.70, 5.2, 4.7, size=13, gap=8)
    footer(s, n)
    notes(s, "[6:00–6:45] Class diagram disederhanakan menjadi perwakilan, "
             "karena menampilkan 12 controller dan 10 model sekaligus akan "
             "tidak terbaca. Tiga baris mencerminkan tiga lapisan. Perhatikan "
             "pola singleton pada kelas Database.")


def slide_sequence_tx(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · UML 4a", "Sequence Diagram: transaksi barang masuk")
    figure(s, "fig_sequence_transaction.png", ML, 1.58, 7.1, 5.05,
           "Gambar 3.6 Sequence Diagram Transaksi Barang Masuk dan Notifikasi")
    x = 7.95
    textbox(s, x, 1.68, 4.8, 0.3, "16 PESAN · 3 FASE", size=11, bold=True,
            color=TEAL)
    phases = [
        ("Fase 1 · Perjalanan permintaan",
         "Browser POST → router verifikasi CSRF → controller."),
        ("Fase 2 · Eksekusi basis data",
         "BEGIN → INSERT induk & rincian → UPDATE stok → COMMIT."),
        ("Fase 3 · Pasca-transaksi",
         "NotificationHelper → bukti transaksi → polling notifikasi."),
    ]
    y = 2.05
    for title, desc in phases:
        rect(s, x, y, 4.75, 1.05, GREY_LIGHT)
        rect(s, x, y, 0.05, 1.05, TEAL)
        textbox(s, x + 0.22, y + 0.13, 4.4, 0.3, title, size=12.5, bold=True,
                color=NAVY)
        textbox(s, x + 0.22, y + 0.46, 4.4, 0.5, desc, size=11, color=GREY)
        y += 1.18
    textbox(s, x, 5.68, 4.75, 0.8,
            "Kotak vertikal pada lifeline adalah **activation bar**: penanda "
            "objek sedang aktif memproses.", size=10.5, color=GREY, italic=True,
            spacing=1.1)
    footer(s, n)
    notes(s, "[6:45–7:30] Sequence transaksi: 16 pesan, tiga fase. Fase 1 "
             "perjalanan permintaan dengan verifikasi CSRF. Fase 2 eksekusi "
             "basis data dalam satu transaksi. Fase 3 pasca-transaksi: "
             "notifikasi dan bukti. Activation bar menunjukkan objek yang "
             "sedang aktif.")


def slide_sequence_ai(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · UML 4b", "Sequence Diagram: pertukaran pesan asisten AI")
    figure(s, "fig_sequence_ai.png", ML, 1.58, 7.1, 5.05,
           "Gambar 3.7 Sequence Diagram Pertukaran Pesan Asisten AI")
    x = 7.95
    textbox(s, x, 1.68, 4.8, 0.3, "DUA SIKLUS TERPISAH", size=11, bold=True,
            color=TEAL)
    card(s, x, 2.02, 4.75, 1.55, "Siklus pemahaman",
         "Pesan → filter topik → buildSnapshot → Gemini → JSON intent → "
         "validasi entitas.", num="SIKLUS 1")
    card(s, x, 3.72, 4.75, 1.55, "Siklus eksekusi",
         "Klik konfirmasi → POST executeAction → BEGIN/COMMIT → log aktivitas.",
         num="SIKLUS 2")
    textbox(s, x, 5.45, 4.75, 0.95,
            "Pemisahan dua siklus inilah yang secara teknis mewujudkan "
            "**human-in-the-loop**: data hanya berubah pada siklus kedua, "
            "setelah pengguna menyetujui.", size=11, color=GREY, spacing=1.15)
    footer(s, n)
    notes(s, "[7:30–8:10] Sequence AI punya dua siklus terpisah. Siklus 1 "
             "memahami pesan menjadi intent. Siklus 2 mengeksekusi setelah "
             "tombol konfirmasi ditekan. Inilah bukti teknis bahwa AI tidak "
             "bisa langsung mengubah data.")


def slide_erd(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · Basis data", "ERD: 12 tabel, 8 relasi utama")
    figure(s, "fig_erd.png", ML, 1.58, 7.0, 5.0,
           "Gambar 3.8 Entity Relationship Diagram")
    x = 7.85
    bullets(s, [
        (0, "**`stok`** menyimpan kuantitas per pasangan barang–rak dengan "
            "kunci unik gabungan **(barang_id, lokasi_id)**."),
        (0, "Kunci unik menjamin tidak pernah ada dua baris stok untuk barang "
            "dan rak yang sama."),
        (0, "Riwayat mutasi disimpan di **`transaksi_detail`**, bukan di "
            "`stok`."),
        (0, "Satu kolom `type` (`in`/`out`/`transfer`) melayani tiga jenis "
            "mutasi tanpa menduplikasi struktur."),
        (0, "Tabel tambahan: `app_settings`, `whatsapp_logs`, "
            "`realtime_notifications`."),
    ], x, 1.70, 4.95, 4.6, size=12.5, gap=8)
    textbox(s, x, 6.30, 4.95, 0.5,
            "Dua pertanyaan berbeda dijawab dua tabel berbeda: \"berapa stok "
            "sekarang\" dan \"mengapa stok berubah\".", size=10.5, color=GREY,
            italic=True, spacing=1.1)
    footer(s, n)
    notes(s, "[8:10–8:50] ERD. Fokus pada tabel stok: kunci unik gabungan "
             "barang dan rak mencegah duplikasi. Riwayat ada di "
             "transaksi_detail. Dengan pemisahan ini, angka stok dan riwayat "
             "tidak mungkin saling bertentangan.")


def slide_wireframe(prs, n):
    s = new(prs)
    header(s, "Tahap 2 · Antarmuka", "Prototipe tiga layar utama")
    figure(s, "fig_wireframe.png", ML, 1.55, 4.35, 5.15,
           "Gambar 3.9 Wireframe Prototipe Antarmuka")
    x = 5.25
    rows = [
        ("Dashboard", "Pola informasi: kartu metrik, grafik tren 7 hari, panel "
         "stok minimum."),
        ("Form transaksi", "Pola input: label di atas kolom, aksi utama hijau "
         "tosca, tombol batal netral."),
        ("Asisten AI", "Pola percakapan: balon pengguna kanan, balon sistem "
         "kiri, kartu konfirmasi kuning."),
    ]
    y = 1.72
    for i, (title, desc) in enumerate(rows, 1):
        rect(s, x, y, 7.4, 1.28, GREY_LIGHT)
        rect(s, x, y, 0.05, 1.28, TEAL)
        textbox(s, x + 0.24, y + 0.16, 0.5, 0.4, str(i), size=20, bold=True,
                color=TEAL, font=HEAD)
        textbox(s, x + 0.78, y + 0.17, 6.4, 0.3, title, size=14, bold=True,
                color=NAVY, font=HEAD)
        textbox(s, x + 0.78, y + 0.52, 6.4, 0.65, desc, size=11.5, color=GREY,
                spacing=1.1)
        y += 1.42
    textbox(s, x, 5.95, 7.4, 0.7,
            "Prototipe dibuat high-fidelity di Figma, lalu diterjemahkan ke "
            "Bootstrap 5.3 dengan warna korporat yang sama.", size=11,
            color=GREY, italic=True, spacing=1.15)
    footer(s, n)
    notes(s, "[8:50–9:20] Tiga layar mewakili tiga pola interaksi: informasi, "
             "input, dan percakapan. Kartu konfirmasi berwarna kuning pada "
             "layar AI adalah elemen kunci human-in-the-loop. Prototipe ini "
             "yang saya terjemahkan ke Bootstrap.")


def slide_implementation(prs, n):
    s = new(prs)
    header(s, "Tahap 3 · Implementasi", "69 berkas sumber, 19.354 baris kode")
    metric(s, ML, 1.60, 2.86, 1.50, "69", "Berkas sumber",
           "64 PHP · 2 JS · 1 CSS · 2 SQL")
    metric(s, ML + 3.05, 1.60, 2.86, 1.50, "19.354", "Baris kode",
           "18.089 baris PHP")
    metric(s, ML + 6.10, 1.60, 2.86, 1.50, "12", "Controller",
           "10 model · 5 helper")
    metric(s, ML + 9.15, 1.60, 2.94, 1.50, "30", "Berkas tampilan",
           "13 folder view")
    textbox(s, ML, 3.32, 5.9, 0.3, "ALUR SATU PERMINTAAN (MVC)", size=11,
            bold=True, color=TEAL)
    steps = [
        ("Browser", "Form + token CSRF"),
        ("Router", "index.php?url=…"),
        ("Controller", "validasi + otorisasi"),
        ("Model", "PDO prepared statement"),
        ("View", "render Bootstrap"),
    ]
    bw, gap = 2.10, 0.33
    y = 3.66
    for i, (name, desc) in enumerate(steps):
        x = ML + i * (bw + gap)
        rect(s, x, y, bw, 0.86, GREY_LIGHT if i % 2 == 0 else WHITE)
        rect(s, x, y, 0.05, 0.86, TEAL)
        textbox(s, x + 0.18, y + 0.12, bw - 0.28, 0.28, name, size=12,
                bold=True, color=NAVY)
        textbox(s, x + 0.18, y + 0.46, bw - 0.26, 0.32, desc, size=9.5,
                color=GREY, spacing=1.0)
        if i < len(steps) - 1:
            arrow(s, x + bw + 0.07, y + 0.33, 0.19, 0.20)
    textbox(s, ML, 4.78, CW, 0.3, "KEPUTUSAN TEKNIS", size=11, bold=True,
            color=TEAL)
    bullets(s, [
        (0, "PHP 8.2 native + MySQL 8.0, tanpa framework — mekanisme ditulis "
            "eksplisit agar dipahami."),
        (0, "Seluruh query memakai prepared statement (anti SQL injection); "
            "setiap POST diverifikasi token CSRF di router."),
        (0, "Logika AI diisolasi pada satu kelas: `WarehouseAiEngine` "
            "(4.853 baris) sehingga controller tetap ringkas."),
        (0, "Setiap operasi multi-tabel dibungkus transaksi basis data "
            "(BEGIN/COMMIT/ROLLBACK)."),
    ], ML, 5.12, CW, 1.75, size=12, gap=6)
    footer(s, n)
    notes(s, "[9:20–9:55] Statistik kode: 69 berkas, 19.354 baris. Alur satu "
             "permintaan mengikuti MVC tanpa framework. Tiga keputusan teknis: "
             "prepared statement, CSRF global, dan isolasi logika AI dalam "
             "satu kelas.")


def slide_ai_core(prs, n):
    s = new(prs)
    header(s, "Tahap 3 · Fitur AI",
           "AI dual-core: dua mesin yang saling menjadi cadangan")
    row1 = ["Pengguna", "AichatController", "WarehouseAiEngine", "Gemini API",
            "JSON intent"]
    row2 = ["Intent + parameter", "Guardrail 3 lapis", "Kartu konfirmasi",
            "Eksekusi aman", "Log aktivitas"]
    bw, bh, gap = 2.18, 0.80, 0.19
    x0 = ML
    y1, y2 = 1.52, 2.52
    for i, label in enumerate(row1):
        x = x0 + i * (bw + gap)
        dark = i in (0, 3)
        rect(s, x, y1, bw, bh, NAVY if dark else GREY_LIGHT,
             MSO_SHAPE.ROUNDED_RECTANGLE, 0.12)
        textbox(s, x + 0.06, y1 + 0.20, bw - 0.12, 0.44, label, size=10.5,
                bold=True, color=WHITE if dark else NAVY,
                align=PP_ALIGN.CENTER)
        if i < len(row1) - 1:
            arrow(s, x + bw + 0.015, y1 + 0.30, 0.16, 0.20)
    for i, label in enumerate(row2):
        x = x0 + i * (bw + gap)
        dark = i in (1, 2)
        rect(s, x, y2, bw, bh, TEAL if dark else GREY_LIGHT,
             MSO_SHAPE.ROUNDED_RECTANGLE, 0.12)
        textbox(s, x + 0.06, y2 + 0.19, bw - 0.12, 0.46, label, size=10.5,
                bold=True, color=WHITE if dark else NAVY,
                align=PP_ALIGN.CENTER)
        if i < len(row2) - 1:
            arrow(s, x + bw + 0.015, y2 + 0.30, 0.16, 0.20)
    textbox(s, ML, 3.52, 12.0, 0.3,
            "Keluaran model bahasa diperlakukan sebagai **usulan**, bukan "
            "perintah: engine memvalidasi intent dan parameter sebelum "
            "menyentuh data.", size=11.5, color=GREY, italic=True)
    textbox(s, ML, 3.98, 5.6, 0.3, "16 INTENT DALAM 4 KELOMPOK", size=11,
            bold=True, color=TEAL)
    groups = [
        ("Baca data (6)",
         "query_stock · query_location · list_empty_racks · stock_summary · "
         "low_stock · transaction_history"),
        ("Analisis (3)",
         "compare_stock · recommend_rack · predict_restock"),
        ("Aksi — butuh konfirmasi (4)",
         "stock_in · stock_out · transfer_stock · adjust_stock"),
        ("Bantuan & di luar domain (3)",
         "help · greeting · out_of_scope"),
    ]
    positions = [(ML, 4.34), (6.75, 4.34), (ML, 5.55), (6.75, 5.55)]
    for (name, items), (x, y) in zip(groups, positions):
        rect(s, x, y, 5.85, 1.02, GREY_LIGHT)
        rect(s, x, y, 0.05, 1.02, TEAL)
        textbox(s, x + 0.20, y + 0.10, 5.5, 0.26, name, size=10, bold=True,
                color=TEAL)
        textbox(s, x + 0.20, y + 0.36, 5.5, 0.58, items, size=9.5, color=GREY,
                spacing=1.05)
    footer(s, n)
    notes(s, "[9:55–10:40] Ini inti fitur AI. Alurnya: pengguna, controller, "
             "engine, Gemini, JSON intent, lalu guardrail, kartu konfirmasi, "
             "eksekusi, log. Ada 16 intent dalam empat kelompok; yang mengubah "
             "data selalu butuh konfirmasi.")


def slide_guardrail(prs, n):
    s = new(prs)
    header(s, "Tahap 3 · Keamanan AI", "Tiga lapis guardrail dan konfirmasi pengguna")
    textbox(s, ML, 1.58, 5.4, 0.3, "TIGA LAPIS PERTAHANAN", size=11, bold=True,
            color=TEAL)
    layers = [
        ("Lapis 1 · Filter topik", "Pesan di luar domain pergudangan ditolak "
         "sebelum memanggil model."),
        ("Lapis 2 · Validasi keluaran", "JSON wajib valid; intent harus "
         "dikenali; entitas dicocokkan ke basis data."),
        ("Lapis 3 · Eksekusi aman", "Whitelist SELECT/SHOW, blacklist "
         "DDL/DML, batas 100 baris, transaksi DB."),
    ]
    y = 1.95
    for title, desc in layers:
        rect(s, ML, y, 5.45, 1.10, GREY_LIGHT)
        rect(s, ML, y, 0.05, 1.10, TEAL)
        textbox(s, ML + 0.22, y + 0.14, 5.0, 0.3, title, size=12.5, bold=True,
                color=NAVY)
        textbox(s, ML + 0.22, y + 0.48, 5.0, 0.55, desc, size=11, color=GREY,
                spacing=1.1)
        y += 1.22
    # panel kode
    panel = rect(s, 6.35, 1.58, 6.36, 2.52, RGBColor(0x10, 0x18, 0x20))
    code = [
        "// helpers/WarehouseAiEngine.php",
        "if (!preg_match('/^\\s*(SELECT|SHOW)\\b/i', $sql)) {",
        "    throw new Exception('Hanya query baca.');",
        "}",
        "foreach (['INSERT','UPDATE','DELETE','DROP',",
        "          'ALTER','TRUNCATE'] as $kw) {",
        "    if (stripos($sql, $kw) !== false) {",
        "        throw new Exception('Ditolak: ' . $kw);",
        "    }",
        "}",
        "$sql .= ' LIMIT 100';",
    ]
    tb = s.shapes.add_textbox(Inches(6.52), Inches(1.70), Inches(6.0),
                              Inches(2.85))
    tf = tb.text_frame
    tf.word_wrap = False
    tf.margin_left = 0
    for i, line in enumerate(code):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.line_spacing = 1.02
        r = p.add_run()
        r.text = line
        r.font.name = MONO
        r.font.size = Pt(10)
        r.font.color.rgb = RGBColor(0xD6, 0xE4, 0xF0) if i else RGBColor(
            0x7F, 0xB8, 0xB0)
    textbox(s, 6.35, 4.24, 6.36, 0.3, "KARTU KONFIRMASI (HITL)", size=11,
            bold=True, color=TEAL)
    steps = ["Ringkas dampak: rak asal 15 → 10, tujuan 5 → 10",
             "Data belum berubah sebelum tombol ditekan",
             "Eksekusi + log bahwa aksi berasal dari asisten AI"]
    y = 4.60
    for i, st in enumerate(steps, 1):
        chip(s, 6.35, y, 0.32, 0.32, str(i), color=TEAL, text_color=WHITE,
             size=10)
        textbox(s, 6.78, y + 0.05, 5.9, 0.3, st, size=11, color=INK)
        y += 0.46
    rect(s, ML, 5.75, 5.45, 0.95, TEAL_LIGHT)
    textbox(s, ML + 0.20, 5.87, 5.05, 0.72,
            "Uji nyata: \"hapus semua data transaksi\" ditolak; \"pindahkan 5 "
            "bearing\" menampilkan kartu konfirmasi tanpa mengubah data.",
            size=10.5, color=NAVY, spacing=1.15)
    footer(s, n)
    notes(s, "[10:40–11:20] Keamanan AI tiga lapis. Kode di kanan adalah "
             "whitelist dan blacklist pada executeSafeSqlQuery, ditambah LIMIT "
             "100. Kartu konfirmasi menunjukkan dampak sebelum eksekusi. Ini "
             "yang saya demonstrasikan nanti.")


def slide_testing(prs, n):
    s = new(prs)
    header(s, "Tahap 3 · Pengujian", "39 pengujian otomatis, 39 PASS, 0 FAIL")
    metric(s, ML, 1.60, 2.86, 1.55, "39", "Automated test",
           "model · helper · smoke test", TEAL)
    metric(s, ML + 3.05, 1.60, 2.86, 1.55, "17", "Skenario black-box",
           "per use case utama", TEAL)
    metric(s, ML + 6.10, 1.60, 2.86, 1.55, "4,4", "UAT (skala 5)",
           "5 responden peran", TEAL)
    metric(s, ML + 9.15, 1.60, 2.94, 1.55, "0", "FAIL",
           "pada basis data bersih", NAVY)
    textbox(s, ML, 3.42, 6.0, 0.3, "APA YANG DIUJI", size=11, bold=True,
            color=TEAL)
    bullets(s, [
        (0, "Model: CRUD barang, stok, transaksi, laporan, pengguna."),
        (0, "Helper: format nomor transaksi, normalisasi nomor WhatsApp, "
            "CSRF, respons JSON."),
        (0, "Bentuk data, bukan hanya nilai kembali: pola `TRX-IN-…`, agregasi "
            "stok, status rak."),
        (0, "Smoke test controller: halaman utama merespons tanpa error."),
    ], ML, 3.74, 6.0, 2.5, size=12, gap=7)
    textbox(s, 6.95, 3.42, 5.8, 0.3, "HASIL PENGUJIAN KOTAK HITAM", size=11,
            bold=True, color=TEAL)
    rows = [
        ["Modul", "Skenario", "Hasil"],
        ["Autentikasi & RBAC", "5", "PASS"],
        ["Transaksi masuk/keluar", "4", "PASS"],
        ["Transfer & penyesuaian", "3", "PASS"],
        ["Asisten AI", "3", "PASS"],
        ["Notifikasi & laporan", "2", "PASS"],
    ]
    table(s, rows, 6.95, 3.74, 5.8, [2.9, 1.3, 1.1], row_h=0.40, size=11)
    textbox(s, 6.95, 6.32, 5.8, 0.5,
            "UAT: kejelasan informasi dan kepercayaan pada kartu konfirmasi "
            "memperoleh nilai tertinggi.", size=10.5, color=GREY, italic=True,
            spacing=1.1)
    footer(s, n)
    notes(s, "[11:20–12:00] Pengujian: 39 kasus otomatis semuanya PASS, 17 "
             "skenario kotak hitam, UAT rata-rata 4,4 dari 5. Yang penting: "
             "pengujian memeriksa bentuk data, bukan hanya apakah fungsi "
             "mengembalikan nilai.")


def slide_cicd(prs, n):
    s = new(prs)
    header(s, "Tahap 3 · DevOps", "Pipeline CI/CD: enam langkah, satu status hijau")
    steps = [
        ("1", "Checkout", "ambil kode"),
        ("2", "Setup PHP", "8.2 + ekstensi"),
        ("3", "Lint", "`php -l` semua berkas"),
        ("4", "MySQL 8.0", "layanan basis data"),
        ("5", "Skema", "impor SQL + patch"),
        ("6", "Test", "39 kasus"),
    ]
    bw, gap = 1.86, 0.14
    y = 1.72
    for i, (num, name, desc) in enumerate(steps):
        x = ML + i * (bw + gap)
        color = TEAL if i < 5 else NAVY
        b = rect(s, x, y, bw, 1.35, color, MSO_SHAPE.ROUNDED_RECTANGLE, 0.10)
        textbox(s, x + 0.12, y + 0.12, bw - 0.24, 0.3, num, size=9.5,
                bold=True, color=RGBColor(0xBF, 0xE0, 0xDC))
        textbox(s, x + 0.12, y + 0.38, bw - 0.24, 0.35, name, size=12.5,
                bold=True, color=WHITE, align=PP_ALIGN.CENTER)
        textbox(s, x + 0.08, y + 0.78, bw - 0.16, 0.5, desc, size=9,
                color=RGBColor(0xE2, 0xEE, 0xEC), align=PP_ALIGN.CENTER)
    textbox(s, ML, 3.22, 6.4, 0.3, "PEMICU DAN BUKTI", size=11, bold=True,
            color=TEAL)
    bullets(s, [
        (0, "Dipicu setiap `push` dan `pull_request` ke cabang main."),
        (0, "Basis data selalu baru, sehingga hasil tidak terpengaruh data "
            "sisa pengujian lokal."),
        (0, "Satu berkas salah sintaks → pipeline berhenti sebelum aplikasi "
            "dibuka."),
        (0, "Riwayat eksekusi menjadi bukti yang dapat diaudit publik."),
    ], ML, 3.56, 6.5, 2.4, size=12, gap=7)
    # panel status
    panel = rect(s, 7.25, 3.50, 5.45, 2.05, GREY_LIGHT)
    rect(s, 7.25, 3.50, 0.06, 2.05, TEAL)
    textbox(s, 7.50, 3.68, 5.0, 0.3, "GitHub Actions · main", size=10.5,
            color=GREY)
    textbox(s, 7.50, 3.96, 5.0, 0.45, "✓ Pipeline passing", size=20, bold=True,
            color=TEAL, font=HEAD)
    textbox(s, 7.50, 4.48, 5.0, 0.3, "build 1m 12s · 6/6 langkah berhasil",
            size=11, color=GREY)
    chip(s, 7.50, 4.92, 1.55, 0.40, "badge hijau", color=TEAL,
         text_color=WHITE, size=10)
    chip(s, 9.20, 4.92, 2.05, 0.40, "39 PASS · 0 FAIL", color=WHITE,
         text_color=TEAL, size=10)
    textbox(s, ML, 6.15, CW, 0.3,
            "Berkas: `.github/workflows/ci.yml` — dapat diperiksa langsung "
            "pada repositori.", size=10.5, color=GREY, italic=True)
    footer(s, n)
    notes(s, "[12:00–12:40] Pipeline enam langkah. Yang paling penting: lint "
             "menghentikan pipeline sebelum aplikasi dibuka, dan basis data "
             "selalu baru sehingga hasil konsisten. Badge hijau di README "
             "membuat kualitas dapat diverifikasi tanpa menjalankan aplikasi.")


def slide_demo(prs, n):
    s = new(prs)
    header(s, "Tahap 4 · Demonstrasi", "Live demo: enam alur yang akan ditunjukkan")
    textbox(s, ML, 1.58, CW, 0.3,
            "Demo dilakukan pada aplikasi yang berjalan (bukan tangkapan "
            "layar) dengan data contoh yang sudah disiapkan.", size=12,
            color=GREY, italic=True)
    items = [
        ("01", "Login & RBAC", "Sidebar Operator vs Admin; akses URL langsung "
         "tetap ditolak.", "40 dtk"),
        ("02", "Master data", "Validasi kode barang unik; relasi barang–rak "
         "many-to-many.", "40 dtk"),
        ("03", "Transaksi", "Masuk 50 unit, tolak qty > stok, transfer antar-rak.",
         "60 dtk"),
        ("04", "Notifikasi", "Badge bertambah tanpa reload; log WhatsApp + "
         "tautan `wa.me`.", "30 dtk"),
        ("05", "Asisten AI", "Query stok, isi rak, transfer + kartu konfirmasi, "
         "guardrail, fallback.", "110 dtk"),
        ("06", "Pengujian & CI", "`php functional_test.php` → 39 PASS; tab "
         "Actions hijau.", "60 dtk"),
    ]
    x0, y0 = ML, 2.02
    w, h, gx, gy = 3.86, 1.92, 0.24, 0.20
    for i, (num, title, body, dur) in enumerate(items):
        x = x0 + (i % 3) * (w + gx)
        y = y0 + (i // 3) * (h + gy)
        rect(s, x, y, w, h, GREY_LIGHT)
        rect(s, x, y, 0.055, h, TEAL)
        textbox(s, x + 0.22, y + 0.14, 1.0, 0.3, num, size=16, bold=True,
                color=TEAL, font=HEAD)
        textbox(s, x + 1.05, y + 0.20, 2.0, 0.3, dur, size=9.5, color=GREY,
                align=PP_ALIGN.RIGHT)
        textbox(s, x + 0.22, y + 0.52, w - 0.44, 0.3, title, size=13, bold=True,
                color=NAVY, font=HEAD)
        textbox(s, x + 0.22, y + 0.88, w - 0.44, 0.9, body, size=10.5,
                color=GREY, spacing=1.1)
    footer(s, n)
    notes(s, "[12:40–16:10] Sekarang demo. Saya akan menunjukkan enam alur "
             "sesuai urutan ini. Perhatikan pada alur 5: kartu konfirmasi "
             "muncul lebih dulu, data belum berubah, lalu saya tekan "
             "konfirmasi. Setelah itu saya uji guardrail dan fallback dengan "
             "kunci API dikosongkan.")


def slide_results(prs, n):
    s = new(prs)
    header(s, "Tahap 4 · Hasil", "Seluruh kebutuhan terpenuhi, dengan bukti")
    rows = [
        ["Kelompok", "Kebutuhan", "Bukti utama", "Status"],
        ["Autentikasi & RBAC", "FR-01 – FR-03", "AuthController, AuthHelper", "Terpenuhi"],
        ["Master data", "FR-05 – FR-07", "Barang/Kategori/LokasiController", "Terpenuhi"],
        ["Transaksi & stok", "FR-08 – FR-14", "Transaksi, Stok (BEGIN/COMMIT)", "Terpenuhi"],
        ["Riwayat & laporan", "FR-15 – FR-17", "Riwayat, Laporan, LogAktivitas", "Terpenuhi"],
        ["Asisten AI", "FR-18 – FR-22", "WarehouseAiEngine, 16 intent", "Terpenuhi"],
        ["Notifikasi & pengaturan", "FR-23 – FR-27", "WhatsAppHelper, Setting", "Terpenuhi"],
        ["Keamanan CSRF", "FR-28", "SecurityHelper di router", "Terpenuhi"],
    ]
    table(s, rows, ML, 1.62, 7.7, [2.3, 1.5, 3.2, 1.2], row_h=0.44, size=10.5)
    textbox(s, 8.62, 1.62, 4.1, 0.3, "CATATAN KETERBATASAN", size=11,
            bold=True, color=GOLD)
    bullets(s, [
        (0, "Kinerja diukur manual, belum terinstrumentasi otomatis."),
        (0, "Cakupan pengujian belum dihitung (tanpa code coverage)."),
        (0, "Belum ada deployment otomatis ke server."),
        (0, "Kualitas jawaban bergantung kuota penyedia eksternal."),
        (0, "Notifikasi memakai polling 5 detik, bukan WebSocket."),
    ], 8.62, 1.96, 4.15, 3.3, size=11, gap=7)
    rect(s, ML, 5.35, 7.7, 1.25, TEAL_LIGHT)
    textbox(s, ML + 0.25, 5.50, 7.2, 0.3, "KESIMPULAN PEMENUHAN", size=10.5,
            bold=True, color=TEAL)
    textbox(s, ML + 0.25, 5.80, 7.2, 0.7,
            "**28 dari 28** kebutuhan fungsional terpenuhi; pemenuhan "
            "dibuktikan tiga cara: keberadaan kode, pengujian otomatis, dan "
            "pengujian kotak hitam.", size=12, color=INK, spacing=1.15)
    textbox(s, 8.62, 5.45, 4.1, 1.2,
            "Keterbatasan dinyatakan terbuka agar pembaca tidak menyimpulkan "
            "melebihi bukti.", size=10.5, color=GREY, italic=True, spacing=1.15)
    footer(s, n)
    notes(s, "[16:10–16:50] Hasil: 28 kebutuhan terpenuhi, masing-masing "
             "dengan bukti kode. Saya juga menyatakan lima keterbatasan secara "
             "terbuka, misalnya belum ada code coverage dan deployment "
             "otomatis.")


def slide_roadmap(prs, n):
    s = new(prs)
    header(s, "Tahap 4 · Rencana", "Pengembangan lanjutan dalam tiga horizon")
    cols = [
        ("Jangka pendek", "1–2 bulan", TEAL, [
            "Ukur code coverage (PHPUnit + phpdbg).",
            "Tambah uji lapisan controller.",
            "Golden set 16 intent untuk deteksi regresi.",
        ], "Fokus: bukti kualitas yang terukur."),
        ("Jangka menengah", "3–6 bulan", NAVY, [
            "Ganti polling dengan WebSocket/SSE.",
            "Deploy otomatis ke staging + smoke test.",
            "Ekspor laporan PDF/Excel.",
            "Audit trail khusus aksi dari AI.",
        ], "Fokus: keandalan operasional harian."),
        ("Jangka panjang", "6–12 bulan", GOLD, [
            "Dukungan multi-gudang (multi-tenant).",
            "Pemindai barcode/RFID.",
            "Demand forecasting dari data mutasi.",
            "Aplikasi mobile operator.",
        ], "Fokus: skala dan cakupan platform."),
    ]
    w, gap = 3.86, 0.24
    for i, (title, period, color, items, focus) in enumerate(cols):
        x = ML + i * (w + gap)
        rect(s, x, 1.62, w, 3.72, GREY_LIGHT)
        rect(s, x, 1.62, w, 0.10, color)
        textbox(s, x + 0.24, 1.86, w - 0.48, 0.35, title, size=15, bold=True,
                color=NAVY, font=HEAD)
        textbox(s, x + 0.24, 2.22, w - 0.48, 0.3, period, size=10.5,
                bold=True, color=color)
        bullets(s, [(0, it) for it in items], x + 0.24, 2.62, w - 0.48, 2.10,
                size=11.5, gap=8)
        hair = s.shapes.add_connector(1, Inches(x + 0.24), Inches(4.62),
                                      Inches(x + w - 0.24), Inches(4.62))
        hair.line.color.rgb = LINE
        hair.line.width = Pt(1)
        strip_style(hair)
        hair.shadow.inherit = False
        textbox(s, x + 0.24, 4.76, w - 0.48, 0.5, focus, size=10.5,
                color=color, italic=True)
    rect(s, ML, 5.62, CW, 0.85, TEAL_LIGHT)
    rect(s, ML, 5.62, 0.06, 0.85, TEAL)
    textbox(s, ML + 0.28, 5.76, CW - 0.55, 0.6,
            "**Prioritas pertama:** pengukuran cakupan pengujian — memberi "
            "peningkatan kepercayaan terbesar dengan usaha paling kecil.",
            size=12.5, color=NAVY)
    footer(s, n)
    notes(s, "[16:50–17:20] Rencana pengembangan tiga horizon. Jangka pendek "
             "fokus pada bukti kualitas: coverage dan golden set intent. "
             "Menengah: real-time dan deployment otomatis. Panjang: "
             "multi-gudang dan mobile.")


def slide_rubric(prs, n):
    s = new(prs)
    header(s, "Pemenuhan rubrik", "Pemetaan artefak terhadap rubrik OBE")
    rows = [
        ["Kriteria", "Bobot", "Bukti pada proyek"],
        ["PRD/SRS & relevansi fitur AI", "20%",
         "28 FR · 14 NFR · 12 US · matriks pelacakan · spesifikasi AI (Bab II)"],
        ["Perancangan UML mandiri", "20%",
         "Use case · activity · class · sequence · ERD · wireframe (Bab III)"],
        ["Implementasi kode & fitur AI", "25%",
         "69 berkas · 19.354 baris · 16 intent · guardrail · HITL (Bab IV–V)"],
        ["Pengujian & GitHub DevOps", "15%",
         "39 test PASS · 17 black-box · UAT 4,4/5 · pipeline hijau (Bab VI–VII)"],
        ["Kualitas presentasi video", "20%",
         "3 video sesuai durasi · webcam · 1080p · tautan pada README"],
    ]
    table(s, rows, ML, 1.65, 12.09, [3.0, 0.9, 7.4], row_h=0.62, head_h=0.44,
          size=11.5, head_size=11.5)
    textbox(s, ML, 5.55, CW, 0.3, "KELENGKAPAN PENGUMPULAN", size=11, bold=True,
            color=TEAL)
    chips = ["Repo proyek-rpl-smartwarehouse", "README lengkap", "3 tautan video",
             "PDF tunggal (PRD+SRS+SDD+uji)", "Pipeline hijau", "Unggah LMS"]
    raw = [0.30 + 0.092 * len(c) for c in chips]
    gap = 0.13
    scale = (CW - gap * (len(chips) - 1)) / sum(raw)
    x = ML
    for c, w in zip(chips, raw):
        cw = w * scale
        chip(s, x, 5.92, cw, 0.40, c, color=GREY_LIGHT, text_color=NAVY,
             size=9.5)
        x += cw + gap
    footer(s, n)
    notes(s, "[17:20–17:50] Pemetaan rubrik. Setiap kriteria punya bukti yang "
             "bisa langsung diperiksa: bab laporan, berkas kode, atau status "
             "pipeline. Kelengkapan pengumpulan ada di baris bawah.")


def slide_conclusion(prs, n):
    s = new(prs)
    header(s, "Penutup", "Kesimpulan")
    metric(s, ML, 1.62, 2.86, 1.55, "28", "Kebutuhan terpenuhi",
           "dari 28 FR", TEAL)
    metric(s, ML + 3.05, 1.62, 2.86, 1.55, "12", "Use case",
           "4 diagram UML + ERD", TEAL)
    metric(s, ML + 6.10, 1.62, 2.86, 1.55, "16", "Intent AI",
           "3 lapis guardrail + HITL", TEAL)
    metric(s, ML + 9.15, 1.62, 2.94, 1.55, "39", "Test PASS",
           "pipeline CI hijau", NAVY)
    bullets(s, [
        (0, "Sistem berjalan penuh: analisis → rancangan → kode → uji → CI/CD."),
        (0, "AI berperan sebagai penerjemah maksud, bukan eksekutor data."),
        (0, "Kualitas dibuktikan otomatis, bukan diklaim."),
    ], ML, 3.55, 6.0, 2.2, size=13.5, gap=9)
    rect(s, 6.95, 3.50, 5.75, 2.30, TEAL_LIGHT)
    textbox(s, 7.20, 3.66, 5.25, 0.3, "SATU KALIMAT", size=10.5, bold=True,
            color=TEAL)
    textbox(s, 7.20, 3.98, 5.25, 1.7,
            "SmartWarehouse AI menunjukkan bahwa aplikasi web konvensional "
            "dapat memperoleh manfaat LLM tanpa menyerahkan kendali atas data "
            "kepada model.", size=13.5, color=NAVY, spacing=1.25)
    textbox(s, ML, 6.15, CW, 0.5,
            "Keterbatasan dan rencana pengembangan telah dinyatakan pada Bab "
            "VIII laporan.", size=10.5, color=GREY, italic=True)
    footer(s, n)
    notes(s, "[17:50–18:20] Kesimpulan dalam empat angka: 28 kebutuhan, 12 use "
             "case, 16 intent, 39 test PASS. Satu kalimat yang saya ingin "
             "dibawa pulang: LLM bisa dipakai tanpa menyerahkan kendali data "
             "kepada model.")


def slide_thanks(prs, n):
    s = new(prs, dark=True)
    rect(s, 0, 0, SW, 0.16, TEAL)
    textbox(s, ML, 2.05, CW, 0.4, "TERIMA KASIH", size=12, bold=True,
            color=RGBColor(0x8F, 0xC9, 0xC2))
    textbox(s, ML, 2.45, 10.5, 0.9, "Diskusi dan masukan", size=44, bold=True,
            color=WHITE, font=HEAD)
    textbox(s, ML, 3.55, 10.5, 0.6,
            "Seluruh kode, laporan, dan tautan video tersedia pada repositori.",
            size=15, color=RGBColor(0xC9, 0xD6, 0xE2))
    rect(s, ML, 4.35, 3.2, 0.02, RGBColor(0x2C, 0x4A, 0x63))
    textbox(s, ML, 4.58, 6.4, 1.2,
            "**Repositori**\ngithub.com/[username]/proyek-rpl-smartwarehouse",
            size=12.5, color=RGBColor(0xC9, 0xD6, 0xE2), spacing=1.3)
    textbox(s, 7.35, 4.58, 5.4, 1.2,
            "**Dokumen**\nLaporan 56 halaman · Naskah video 24 halaman",
            size=12.5, color=RGBColor(0xC9, 0xD6, 0xE2), spacing=1.3)
    textbox(s, ML, 6.55, CW, 0.3,
            "[Nama Lengkap Mahasiswa] · [NIM] · Kelas [Kelas] · "
            "STMIK IKMI Cirebon", size=11,
            color=RGBColor(0x7F, 0x9A, 0xB3))
    notes(s, "[18:20–18:40] Terima kasih. Saya siap menerima pertanyaan, "
             "terutama mengenai rancangan basis data, guardrail AI, dan "
             "pengujian.")


# ------------------------------------------------------------------ build
def build():
    prs = Presentation()
    prs.slide_width = Inches(SW)
    prs.slide_height = Inches(SH)
    prs.core_properties.title = "SmartWarehouse AI — Presentasi Proyek Akhir"
    prs.core_properties.author = "[Nama Lengkap Mahasiswa]"
    prs.core_properties.subject = "Proyek Akhir Rekayasa Perangkat Lunak"

    slide_title(prs, 1)
    slide_agenda(prs, 2)
    slide_divider(prs, 3, "01", "Analisis Kebutuhan",
                  "Dari enam masalah nyata menjadi 28 kebutuhan yang dapat "
                  "dilacak sampai kasus uji.",
                  ["PRD/SRS sesuai IEEE 830 / ISO 29148",
                   "28 FR · 14 NFR · 12 user story",
                   "Ruang lingkup dan rencana fitur AI"])
    slide_problems(prs, 4)
    slide_solution(prs, 5)
    slide_scope(prs, 6)
    slide_prd(prs, 7)
    slide_divider(prs, 8, "02", "Perancangan Sistem",
                  "Empat diagram UML, satu ERD, dan prototipe antarmuka yang "
                  "saling konsisten.",
                  ["Use case · activity · class · sequence",
                   "ERD 12 tabel + kamus data",
                   "Wireframe tiga layar utama"])
    slide_architecture(prs, 9)
    slide_usecase(prs, 10)
    slide_activity(prs, 11)
    slide_class(prs, 12)
    slide_sequence_tx(prs, 13)
    slide_sequence_ai(prs, 14)
    slide_erd(prs, 15)
    slide_wireframe(prs, 16)
    slide_divider(prs, 17, "03", "Implementasi, AI & Pengujian",
                  "Kode yang berjalan, AI yang aman, dan kualitas yang "
                  "dibuktikan otomatis.",
                  ["69 berkas · 19.354 baris · pola MVC",
                   "16 intent · 3 lapis guardrail · HITL",
                   "39 test PASS · pipeline CI/CD hijau"])
    slide_implementation(prs, 18)
    slide_ai_core(prs, 19)
    slide_guardrail(prs, 20)
    slide_testing(prs, 21)
    slide_cicd(prs, 22)
    slide_demo(prs, 23)
    slide_results(prs, 24)
    slide_roadmap(prs, 25)
    slide_rubric(prs, 26)
    slide_conclusion(prs, 27)
    slide_thanks(prs, 28)

    out = os.path.join(BASE, "PRESENTASI_PROYEK_AKHIR.pptx")
    prs.save(out)
    print("OK ->", out, "(%d slide)" % len(prs.slides))


if __name__ == "__main__":
    build()
