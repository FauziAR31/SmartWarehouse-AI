# -*- coding: utf-8 -*-
"""
Konversi Markdown (laporan / naskah video) menjadi DOCX siap cetak.

Dipakai:
    python tools/build_docx.py LAPORAN_PROYEK_AKHIR_RPL.md LAPORAN_PROYEK_AKHIR_RPL.docx
"""
import os
import re
import sys

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

BODY_FONT = "Times New Roman"
HEAD_FONT = "Times New Roman"
MONO_FONT = "Consolas"
BODY_SIZE = 11.0
INK = RGBColor(0x00, 0x00, 0x00)
NAVY = RGBColor(0x0B, 0x1F, 0x33)
GREY = RGBColor(0x44, 0x4B, 0x55)
ACCENT = RGBColor(0x0F, 0x76, 0x6E)

CONTENT_WIDTH_CM = 16.0
IMG_WIDTH_CM = 15.6
IMG_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

PLAIN_SECTIONS = {"DAFTAR ISI", "DAFTAR GAMBAR", "DAFTAR TABEL"}


# ------------------------------------------------------------------ util xml
def shade(element, hexcolor):
    pr = element.get_or_add_tcPr() if hasattr(element, "get_or_add_tcPr") else element
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hexcolor)
    pr.append(shd)


def cell_shade(cell, hexcolor):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hexcolor)
    tcPr.append(shd)


def para_shade(paragraph, hexcolor):
    pPr = paragraph._p.get_or_add_pPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hexcolor)
    pPr.append(shd)


def set_cell_margins(table, top=60, start=100, bottom=60, end=100):
    tblPr = table._tbl.tblPr
    mar = OxmlElement("w:tblCellMar")
    for tag, val in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        node = OxmlElement("w:" + tag)
        node.set(qn("w:w"), str(val))
        node.set(qn("w:type"), "dxa")
        mar.append(node)
    tblPr.append(mar)


def repeat_header(row):
    trPr = row._tr.get_or_add_trPr()
    el = OxmlElement("w:tblHeader")
    el.set(qn("w:val"), "true")
    trPr.append(el)


def add_field(paragraph, instr):
    run = paragraph.add_run()
    f1 = OxmlElement("w:fldChar")
    f1.set(qn("w:fldCharType"), "begin")
    it = OxmlElement("w:instrText")
    it.set(qn("xml:space"), "preserve")
    it.text = instr
    f2 = OxmlElement("w:fldChar")
    f2.set(qn("w:fldCharType"), "separate")
    t = OxmlElement("w:t")
    t.text = "1"
    f3 = OxmlElement("w:fldChar")
    f3.set(qn("w:fldCharType"), "end")
    run._r.append(f1)
    run._r.append(it)
    run._r.append(f2)
    run._r.append(t)
    run._r.append(f3)


# ------------------------------------------------------------------ styles
def setup_document(doc, footer_label="SmartWarehouse AI — Laporan Proyek Akhir"):
    sec = doc.sections[0]
    sec.page_width = Cm(21.0)
    sec.page_height = Cm(29.7)
    sec.top_margin = Cm(2.5)
    sec.bottom_margin = Cm(2.5)
    sec.left_margin = Cm(2.5)
    sec.right_margin = Cm(2.5)

    normal = doc.styles["Normal"]
    normal.font.name = BODY_FONT
    normal.font.size = Pt(BODY_SIZE)
    normal.font.color.rgb = INK
    normal._element.rPr.rFonts.set(qn("w:eastAsia"), BODY_FONT)
    pf = normal.paragraph_format
    pf.line_spacing = 1.5
    pf.space_before = Pt(0)
    pf.space_after = Pt(6)
    pf.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    specs = {
        "Heading 1": (16, True, NAVY, 18, 10),
        "Heading 2": (13.5, True, NAVY, 14, 6),
        "Heading 3": (12, True, NAVY, 12, 5),
        "Heading 4": (11.5, True, GREY, 10, 4),
    }
    for name, (size, bold, color, before, after) in specs.items():
        st = doc.styles[name]
        st.font.name = HEAD_FONT
        st.font.size = Pt(size)
        st.font.bold = bold
        st.font.color.rgb = color
        st.font.italic = False
        st._element.rPr.rFonts.set(qn("w:eastAsia"), HEAD_FONT)
        st.paragraph_format.space_before = Pt(before)
        st.paragraph_format.space_after = Pt(after)
        st.paragraph_format.line_spacing = 1.25
        st.paragraph_format.keep_with_next = True
        st.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT

    # footer
    footer = sec.footer
    fp = footer.paragraphs[0]
    fp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = fp.add_run(footer_label + "   |   ")
    r.font.size = Pt(8.5)
    r.font.name = BODY_FONT
    r.font.color.rgb = GREY
    add_field(fp, "PAGE")
    for run in fp.runs:
        run.font.size = Pt(8.5)
        run.font.name = BODY_FONT
        run.font.color.rgb = GREY


# ------------------------------------------------------------------ inline
TOKEN = re.compile(r"(\*\*.+?\*\*|`[^`]+`|\*[^*\n]+?\*)")


def add_inline(paragraph, text, base_size=None, base_bold=False, color=None):
    """Tulis teks dengan penanda **bold**, *italic*, `code`."""
    text = text.replace("\\|", "|")
    for part in TOKEN.split(text):
        if not part:
            continue
        if part.startswith("**") and part.endswith("**") and len(part) > 4:
            run = paragraph.add_run(part[2:-2])
            run.bold = True
        elif part.startswith("`") and part.endswith("`") and len(part) > 2:
            run = paragraph.add_run(part[1:-1])
            run.font.name = MONO_FONT
            run.font.size = Pt((base_size or BODY_SIZE) - 1.0)
            run._element.rPr.rFonts.set(qn("w:eastAsia"), MONO_FONT)
        elif part.startswith("*") and part.endswith("*") and len(part) > 2:
            run = paragraph.add_run(part[1:-1])
            run.italic = True
        else:
            run = paragraph.add_run(part)
        if base_bold:
            run.bold = True
        if base_size and run.font.size is None:
            run.font.size = Pt(base_size)
        if color is not None:
            run.font.color.rgb = color
    return paragraph


# ------------------------------------------------------------------ blocks
def add_cover(doc, lines):
    for i, line in enumerate(lines):
        text = line.strip()
        if not text or text == "---":
            continue
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        if text.startswith("# "):
            p.paragraph_format.space_before = Pt(120)
            p.paragraph_format.space_after = Pt(18)
            r = p.add_run(text[2:])
            r.bold = True
            r.font.size = Pt(24)
            r.font.name = HEAD_FONT
            r.font.color.rgb = NAVY
        elif text.startswith("## "):
            p.paragraph_format.space_after = Pt(24)
            r = p.add_run(text[3:])
            r.bold = True
            r.font.size = Pt(14)
            r.font.name = HEAD_FONT
            r.font.color.rgb = NAVY
        else:
            p.paragraph_format.space_after = Pt(2)
            p.paragraph_format.line_spacing = 1.3
            add_inline(p, text, base_size=11)
            for run in p.runs:
                run.font.color.rgb = GREY
    p = doc.add_paragraph()
    p.add_run().add_break(WD_BREAK.PAGE)


def add_heading(doc, level, text, page_break):
    if level == 1 and page_break:
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(0)
        p.add_run().add_break(WD_BREAK.PAGE)
    h = doc.add_heading(level=level)
    add_inline(h, text)
    for run in h.runs:
        run.font.name = HEAD_FONT
        run.font.color.rgb = NAVY
    if level == 1:
        # garis bawah judul bab
        pPr = h._p.get_or_add_pPr()
        bdr = OxmlElement("w:pBdr")
        bottom = OxmlElement("w:bottom")
        bottom.set(qn("w:val"), "single")
        bottom.set(qn("w:sz"), "8")
        bottom.set(qn("w:space"), "6")
        bottom.set(qn("w:color"), "0B1F33")
        bdr.append(bottom)
        pPr.append(bdr)
    return h


def add_paragraph(doc, text):
    stripped = text.strip()
    p = doc.add_paragraph()
    # paragraf yang seluruhnya bold dan berawalan Tabel/Gambar = caption tabel
    if re.fullmatch(r"\*\*[^*]+\*\*", stripped) and stripped[2:].lower().startswith(
        ("tabel", "gambar", "daftar")
    ):
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.keep_with_next = True
        p.paragraph_format.space_before = Pt(10)
        p.paragraph_format.space_after = Pt(4)
        add_inline(p, stripped, base_size=10.5, base_bold=True)
        return p
    # label pendek berakhiran titik dua (mis. "Narasi:", "Visual (live):")
    if re.fullmatch(r"\*\*[^*]+:\*\*", stripped):
        p.paragraph_format.keep_with_next = True
        p.paragraph_format.space_before = Pt(8)
        p.paragraph_format.space_after = Pt(3)
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        add_inline(p, stripped, base_bold=True)
        return p
    if stripped.startswith("> "):
        p.paragraph_format.left_indent = Cm(0.8)
        p.paragraph_format.right_indent = Cm(0.4)
        p.paragraph_format.space_before = Pt(4)
        p.paragraph_format.space_after = Pt(8)
        p.paragraph_format.keep_together = True
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        add_inline(p, stripped[2:], base_size=10.5)
        for run in p.runs:
            run.italic = True
            run.font.color.rgb = GREY
        para_shade(p, "F4F6F9")
        return p
    add_inline(p, stripped)
    return p


def add_list_item(doc, text, ordered, number=None, level=0, plain=False):
    p = doc.add_paragraph()
    if plain:
        p.paragraph_format.left_indent = Cm(0.0)
        p.paragraph_format.first_line_indent = Cm(0.0)
        p.paragraph_format.space_after = Pt(2)
        p.paragraph_format.line_spacing = 1.2
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        add_inline(p, text)
        return p
    indent = 0.9 + 0.6 * level
    p.paragraph_format.left_indent = Cm(indent)
    p.paragraph_format.first_line_indent = Cm(-0.55)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.4
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    if ordered:
        p.add_run("%s. " % number)
    else:
        p.add_run("•  " if level == 0 else "–  ")
    add_inline(p, text)
    return p


def add_code(doc, lines):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = True
    cell = table.cell(0, 0)
    cell_shade(cell, "F2F4F7")
    set_cell_margins(table, 100, 140, 100, 140)
    # border tipis
    tblPr = table._tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        el = OxmlElement("w:" + edge)
        el.set(qn("w:val"), "single")
        el.set(qn("w:sz"), "4")
        el.set(qn("w:color"), "D6DEE8")
        borders.append(el)
    tblPr.append(borders)

    para = cell.paragraphs[0]
    para.paragraph_format.line_spacing = 1.0
    para.paragraph_format.space_after = Pt(0)
    para.alignment = WD_ALIGN_PARAGRAPH.LEFT
    for i, line in enumerate(lines):
        run = para.add_run(line)
        run.font.name = MONO_FONT
        run.font.size = Pt(8.5)
        run._element.rPr.rFonts.set(qn("w:eastAsia"), MONO_FONT)
        if i < len(lines) - 1:
            run.add_break()
    spacer = doc.add_paragraph()
    spacer.paragraph_format.space_after = Pt(6)
    spacer.paragraph_format.line_spacing = 1.0


def add_table(doc, rows):
    header = rows[0]
    body = rows[1:]
    ncols = len(header)
    table = doc.add_table(rows=1, cols=ncols)
    table.style = "Table Grid"
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    set_cell_margins(table, 50, 90, 50, 90)

    widths = compute_widths(header, body, CONTENT_WIDTH_CM)
    layout = OxmlElement("w:tblLayout")
    layout.set(qn("w:type"), "fixed")
    table._tbl.tblPr.append(layout)
    for i, gc in enumerate(table._tbl.tblGrid.gridCol_lst):
        gc.set(qn("w:w"), str(int(widths[i] * 567)))

    fs = 9.0 if ncols <= 4 else 8.3

    hdr = table.rows[0]
    repeat_header(hdr)
    no_split(hdr)
    for i, text in enumerate(header):
        cell = hdr.cells[i]
        cell.width = Cm(widths[i])
        cell_shade(cell, "DCE6F1")
        p = cell.paragraphs[0]
        p.paragraph_format.space_after = Pt(2)
        p.paragraph_format.line_spacing = 1.1
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        add_inline(p, text, base_size=fs, base_bold=True)

    for r in body:
        row = table.add_row()
        no_split(row)
        for i in range(ncols):
            text = r[i] if i < len(r) else ""
            cell = row.cells[i]
            cell.width = Cm(widths[i])
            p = cell.paragraphs[0]
            p.paragraph_format.space_after = Pt(2)
            p.paragraph_format.line_spacing = 1.1
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            add_inline(p, text, base_size=fs)

    if len(body) <= 10:
        for row in table.rows[:-1]:
            for cell in row.cells:
                for p in cell.paragraphs:
                    p.paragraph_format.keep_with_next = True

    spacer = doc.add_paragraph()
    spacer.paragraph_format.space_after = Pt(6)
    spacer.paragraph_format.line_spacing = 1.0


def compute_widths(header, body, total_cm):
    n = len(header)
    weights = []
    longest = []
    for i in range(n):
        vals = sorted(len(r[i]) for r in body if i < len(r) and r[i])
        med = vals[len(vals) // 2] if vals else 0
        w = max(len(header[i]) * 0.7 + 2.0, med * 1.1, 5.0)
        weights.append(w ** 0.8)
        words = [w2 for r in ([header] + body) for w2 in
                 (r[i] if i < len(r) else "").split() if w2]
        longest.append(max((len(w2) for w2 in words), default=4))
    s = sum(weights)
    widths = [total_cm * w / s for w in weights]
    minimum = 1.7 if n <= 5 else 1.45
    fixed = [max(w, minimum) for w in widths]
    extra = sum(fixed) - total_cm
    if extra > 0:
        flex = [max(w - minimum, 0.01) for w in fixed]
        sf = sum(flex)
        fixed = [w - extra * f / sf for w, f in zip(fixed, flex)]

    # jangan sampai satu kata terpotong di tengah (mis. "Pemelihara|an")
    need = [min(lw * 0.150 + 0.30, total_cm * 0.42) for lw in longest]
    if sum(need) <= total_cm * 0.98:
        fixed = [max(w, nd) for w, nd in zip(fixed, need)]
        extra = sum(fixed) - total_cm
        if extra > 0:
            flex = [max(w - nd, 0.01) for w, nd in zip(fixed, need)]
            sf = sum(flex)
            fixed = [w - extra * f / sf for w, f in zip(fixed, flex)]
    return fixed


def no_split(row):
    trPr = row._tr.get_or_add_trPr()
    el = OxmlElement("w:cantSplit")
    el.set(qn("w:val"), "true")
    trPr.append(el)


def add_toc_field(doc):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(6)
    run = p.add_run()
    f1 = OxmlElement("w:fldChar")
    f1.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = 'TOC \\o "1-2" \\h \\z \\u'
    f2 = OxmlElement("w:fldChar")
    f2.set(qn("w:fldCharType"), "separate")
    t = OxmlElement("w:t")
    t.text = "Daftar isi akan diperbarui saat dokumen dibuka."
    f3 = OxmlElement("w:fldChar")
    f3.set(qn("w:fldCharType"), "end")
    for el in (f1, instr, f2, t, f3):
        run._r.append(el)


def add_image(doc, alt, path):
    full = path if os.path.isabs(path) else os.path.join(IMG_DIR, path)
    if not os.path.exists(full):
        p = doc.add_paragraph()
        add_inline(p, "[gambar tidak ditemukan: %s]" % path)
        return
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(8)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    run = p.add_run()
    run.add_picture(full, width=Cm(IMG_WIDTH_CM))

    cap = doc.add_paragraph()
    cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    cap.paragraph_format.space_before = Pt(0)
    cap.paragraph_format.space_after = Pt(14)
    cap.paragraph_format.line_spacing = 1.15
    add_inline(cap, alt, base_size=9.5)
    for r in cap.runs:
        r.italic = True
        r.font.color.rgb = GREY


# ------------------------------------------------------------------ parser
IMG_RE = re.compile(r"^!\[(.*?)\]\((.*?)\)\s*$")
HEAD_RE = re.compile(r"^(#{1,4})\s+(.*)$")
OL_RE = re.compile(r"^(\d+)\.\s+(.*)$")
UL_RE = re.compile(r"^[-*]\s+(.*)$")


def split_table_row(line):
    s = line.strip()
    if s.startswith("|"):
        s = s[1:]
    if s.endswith("|"):
        s = s[:-1]
    parts = re.split(r"(?<!\\)\|", s)
    return [p.strip() for p in parts]


def is_separator(line):
    s = line.strip()
    return bool(re.fullmatch(r"\|?[\s:|-]+\|?", s)) and "-" in s


def convert(md_path, docx_path):
    with open(md_path, "r", encoding="utf-8") as f:
        raw = f.read().replace("\r\n", "\n")
    lines = raw.split("\n")

    doc = Document()
    label = ("SmartWarehouse AI — Naskah Video Presentasi"
             if "NASKAH" in os.path.basename(md_path).upper()
             else "SmartWarehouse AI — Laporan Proyek Akhir")
    setup_document(doc, label)
    doc.core_properties.title = os.path.splitext(os.path.basename(md_path))[0]
    doc.core_properties.author = "[Nama Lengkap Mahasiswa]"
    doc.core_properties.subject = "Proyek Akhir Rekayasa Perangkat Lunak"

    # ---- cover: semua baris sebelum '---' pertama
    cover_end = 0
    for i, line in enumerate(lines):
        if line.strip() == "---":
            cover_end = i
            break
    add_cover(doc, lines[:cover_end])
    i = cover_end + 1

    first_h1 = True
    ordered_counter = 0
    skip_caption = False
    section = ""
    skip_section_list = False
    body_started = False

    while i < len(lines):
        line = lines[i]
        stripped = line.strip()

        if skip_caption:
            skip_caption = False
            j = i
            while j < len(lines) and not lines[j].strip():
                j += 1
            if j < len(lines):
                cand = lines[j].strip()
                if (cand.startswith("*") and cand.endswith("*")
                        and not cand.startswith("**") and len(cand) > 2):
                    i = j + 1
                    continue

        # code fence
        if stripped.startswith("```"):
            i += 1
            buf = []
            while i < len(lines) and not lines[i].strip().startswith("```"):
                buf.append(lines[i].rstrip())
                i += 1
            i += 1
            add_code(doc, buf if buf else [""])
            body_started = True
            ordered_counter = 0
            continue

        # gambar
        m = IMG_RE.match(stripped)
        if m:
            add_image(doc, m.group(1), m.group(2))
            body_started = True
            skip_caption = True
            ordered_counter = 0
            i += 1
            continue

        # heading
        m = HEAD_RE.match(stripped)
        if m:
            level = len(m.group(1))
            title = m.group(2).strip()
            add_heading(doc, level, title, page_break=(level == 1 and body_started))
            body_started = True
            section = title.upper()
            ordered_counter = 0
            skip_section_list = False
            if section == "DAFTAR ISI":
                add_toc_field(doc)
                skip_section_list = True
            i += 1
            continue

        # horizontal rule
        if stripped in ("---", "***", "___"):
            ordered_counter = 0
            i += 1
            continue

        # tabel
        if stripped.startswith("|") and i + 1 < len(lines) and is_separator(lines[i + 1]):
            header = split_table_row(lines[i])
            i += 2
            rows = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                rows.append(split_table_row(lines[i]))
                i += 1
            ncols = len(header)
            rows = [(r + [""] * ncols)[:ncols] for r in rows]
            add_table(doc, [header] + rows)
            body_started = True
            ordered_counter = 0
            continue

        # list
        m = OL_RE.match(stripped)
        if m:
            if skip_section_list:
                i += 1
                continue
            ordered_counter += 1
            level = 1 if line[:2] == "  " else 0
            add_list_item(doc, m.group(2), True, m.group(1), level=level)
            body_started = True
            i += 1
            continue
        m = UL_RE.match(stripped)
        if m:
            if skip_section_list:
                i += 1
                continue
            ordered_counter = 0
            level = 1 if line[:2] == "  " else 0
            add_list_item(doc, m.group(1), False, level=level,
                          plain=(section in PLAIN_SECTIONS))
            body_started = True
            i += 1
            continue

        if not stripped:
            ordered_counter = 0
            i += 1
            continue

        # paragraf: gabungkan baris berurutan
        buf = [stripped]
        i += 1
        while i < len(lines):
            nxt = lines[i].strip()
            if (not nxt or nxt.startswith(("#", "|", "```", ">", "!["))
                    or IMG_RE.match(nxt) or OL_RE.match(nxt) or UL_RE.match(nxt)
                    or nxt in ("---", "***", "___")):
                break
            buf.append(nxt)
            i += 1
        add_paragraph(doc, " ".join(buf))
        body_started = True
        ordered_counter = 0

    doc.save(docx_path)
    print("OK ->", docx_path)


if __name__ == "__main__":
    base = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    pairs = [
        ("LAPORAN_PROYEK_AKHIR_RPL.md", "LAPORAN_PROYEK_AKHIR_RPL.docx"),
        ("NASKAH_VIDEO_PROYEK_AKHIR.md", "NASKAH_VIDEO_PROYEK_AKHIR.docx"),
    ]
    if len(sys.argv) == 3:
        pairs = [(sys.argv[1], sys.argv[2])]
    for src, dst in pairs:
        convert(os.path.join(base, src), os.path.join(base, dst))
