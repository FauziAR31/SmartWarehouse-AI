# -*- coding: utf-8 -*-
"""
Membangun gambar UML / ERD / wireframe untuk laporan proyek akhir RPL.
Output: docs/assets/*.png  (tanpa caption — caption ditambahkan di dokumen)
"""
import math
import os

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import (Circle, Ellipse, FancyArrowPatch,
                                FancyBboxPatch, Polygon, Rectangle)

# ------------------------------------------------------------------ tema
NAVY = "#0B1F33"
NAVY2 = "#14324F"
TEAL = "#0F766E"
TEAL_L = "#E7F4F2"
BLUE = "#1D4ED8"
BLUE_L = "#EAF0FB"
AMBER = "#B45309"
AMBER_L = "#FEF3C7"
ROSE = "#BE123C"
ROSE_L = "#FEE7EC"
GREY = "#475569"
GREY_L = "#F4F6F9"
EDGE = "#8FA3B8"
INK = "#0F172A"
WHITE = "#FFFFFF"

plt.rcParams.update({
    "font.family": "DejaVu Sans",
    "font.size": 8,
    "savefig.facecolor": "white",
    "figure.facecolor": "white",
})

OUT = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                   "assets")
os.makedirs(OUT, exist_ok=True)

WIDTH_IN = 6.3          # 16 cm
DPI = 300


def new_fig(height_in, xmax=100.0):
    fig = plt.figure(figsize=(WIDTH_IN, height_in), dpi=DPI)
    ax = fig.add_axes([0, 0, 1, 1])
    ymax = xmax * height_in / WIDTH_IN
    ax.set_xlim(0, xmax)
    ax.set_ylim(0, ymax)
    ax.set_aspect("equal")
    ax.axis("off")
    return fig, ax, xmax, ymax


def new_fig_u(h_units):
    """Kanvas dengan tinggi h_units pada skala x 0..100."""
    fig, ax, _, _ = new_fig(h_units * WIDTH_IN / 100.0, 100)
    return fig, ax


def txt(ax, x, y, s, fs=8, color=INK, bold=False, ha="center", va="center",
        italic=False, rot=0, bbox=None, lsp=1.25, zorder=6):
    return ax.text(x, y, s, fontsize=fs, color=color,
                   fontweight="bold" if bold else "normal",
                   ha=ha, va=va, rotation=rot, linespacing=lsp,
                   style="italic" if italic else "normal",
                   bbox=bbox, zorder=zorder)


def box(ax, x, y, w, h, text="", fc=WHITE, ec=NAVY, fs=8, bold=False, lw=1.0,
        rounded=True, color=INK, zorder=3, ls="-", lsp=1.3):
    if rounded:
        p = FancyBboxPatch((x - w / 2, y - h / 2), w, h,
                           boxstyle="round,pad=0,rounding_size=1.4",
                           linewidth=lw, edgecolor=ec, facecolor=fc,
                           zorder=zorder, linestyle=ls)
    else:
        p = Rectangle((x - w / 2, y - h / 2), w, h, linewidth=lw,
                      edgecolor=ec, facecolor=fc, zorder=zorder, linestyle=ls)
    ax.add_patch(p)
    if text:
        txt(ax, x, y, text, fs=fs, color=color, bold=bold, lsp=lsp)
    return p


def ellipse(ax, x, y, w, h, text="", fc=WHITE, ec=NAVY, fs=7.4, lw=0.9,
            color=INK, zorder=3):
    ax.add_patch(Ellipse((x, y), w, h, facecolor=fc, edgecolor=ec,
                         linewidth=lw, zorder=zorder))
    if text:
        txt(ax, x, y, text, fs=fs, color=color, lsp=1.2)


def ell_edge(cx, cy, w, h, px, py):
    """Titik potong tepi elips pada arah (px,py)."""
    a, b = w / 2.0, h / 2.0
    dx, dy = px - cx, py - cy
    t = 1.0 / math.sqrt((dx / a) ** 2 + (dy / b) ** 2)
    return (cx + t * dx, cy + t * dy)


def diamond(ax, x, y, w, h, text="", fc=AMBER_L, ec=AMBER, fs=7.0, lw=1.0):
    pts = [(x, y + h / 2), (x + w / 2, y), (x, y - h / 2), (x - w / 2, y)]
    ax.add_patch(Polygon(pts, closed=True, facecolor=fc, edgecolor=ec,
                         linewidth=lw, zorder=3))
    if text:
        txt(ax, x, y, text, fs=fs, color="#7C2D12", lsp=1.15)


def arrow(ax, p1, p2, color=NAVY2, lw=1.0, style="-|>", ls="-", shrink=1.5,
          rad=0.0, zorder=4, ms=8):
    a = FancyArrowPatch(p1, p2, arrowstyle=style, mutation_scale=ms,
                        color=color, linewidth=lw, linestyle=ls,
                        shrinkA=shrink, shrinkB=shrink, zorder=zorder,
                        connectionstyle="arc3,rad=%s" % rad)
    ax.add_patch(a)
    return a


def line(ax, p1, p2, color=EDGE, lw=0.9, ls="-", zorder=2):
    ax.plot([p1[0], p2[0]], [p1[1], p2[1]], color=color, linewidth=lw,
            linestyle=ls, zorder=zorder, solid_capstyle="round")


def poly(ax, pts, color=EDGE, lw=0.9, ls="-", zorder=2):
    xs = [p[0] for p in pts]
    ys = [p[1] for p in pts]
    ax.plot(xs, ys, color=color, linewidth=lw, linestyle=ls, zorder=zorder,
            solid_capstyle="round", solid_joinstyle="round")


def actor(ax, x, y, label, fs=7.6, color=NAVY, scale=1.0):
    r = 1.55 * scale
    ax.add_patch(Circle((x, y + 4.2 * scale), r, facecolor=WHITE,
                        edgecolor=color, linewidth=1.0, zorder=5))
    line(ax, (x, y + 2.65 * scale), (x, y - 1.1 * scale), color, 1.0, zorder=5)
    line(ax, (x - 2.2 * scale, y + 1.5 * scale),
         (x + 2.2 * scale, y + 1.5 * scale), color, 1.0, zorder=5)
    line(ax, (x, y - 1.1 * scale), (x - 1.9 * scale, y - 4.0 * scale), color,
         1.0, zorder=5)
    line(ax, (x, y - 1.1 * scale), (x + 1.9 * scale, y - 4.0 * scale), color,
         1.0, zorder=5)
    txt(ax, x, y - 6.2 * scale, label, fs=fs, bold=True, color=color)


def lifeline(ax, x, ytop, ybot, label, fs=6.4, w=13.5):
    box(ax, x, ytop, w, 5.4, label, fc=TEAL_L, ec=TEAL, fs=fs, bold=True,
        color=NAVY, lw=0.8)
    ax.plot([x, x], [ytop - 2.7, ybot], color=EDGE, linewidth=0.75,
            linestyle=(0, (3, 2.4)), zorder=1)


def msg(ax, x1, x2, y, text, fs=6.2, color=NAVY2, ls="-", num=None,
        style="-|>", ms=6.5):
    arrow(ax, (x1, y), (x2, y), color=color, lw=0.8, style=style, ls=ls,
          ms=ms, shrink=1.0)
    label = ("%s. %s" % (num, text)) if num else text
    txt(ax, (x1 + x2) / 2.0, y + 1.15, label, fs=fs, color=color,
        bbox=dict(boxstyle="square,pad=0.10", fc="white", ec="none"))


def selfmsg(ax, x, y, text, fs=6.0, color=NAVY2, w=4.6, h=2.3):
    poly(ax, [(x + 0.6, y), (x + w, y), (x + w, y - h), (x + 1.1, y - h)],
         color=color, lw=0.8, zorder=4)
    arrow(ax, (x + w, y - h), (x + 0.9, y - h), color=color, lw=0.8, ms=6,
          shrink=0.2, zorder=5)
    txt(ax, x + w + 0.9, y - h / 2.0, text, fs=fs, color=color, ha="left")


def activation(ax, x, y1, y2):
    ax.add_patch(Rectangle((x - 0.42, y2), 0.84, y1 - y2, facecolor="#CBD5E1",
                           edgecolor=GREY, linewidth=0.5, zorder=3))


def card(ax, x, y, s, fs=6.2):
    txt(ax, x, y, s, fs=fs, color=GREY, bold=True,
        bbox=dict(boxstyle="round,pad=0.10", fc="white", ec="none"))


def save(fig, name):
    path = os.path.join(OUT, name)
    fig.savefig(path, dpi=DPI, facecolor="white")
    plt.close(fig)
    print("OK", name)


# ================================================================== USE CASE
def usecase():
    H = 108.0
    fig, ax = new_fig_u(H)

    ax.add_patch(FancyBboxPatch((26, 4), 62, H - 8,
                                boxstyle="round,pad=0,rounding_size=2",
                                linewidth=1.2, edgecolor=NAVY,
                                facecolor="#FBFCFE", zorder=1))
    txt(ax, 57, 100.4, "SmartWarehouse AI", fs=10.5, bold=True, color=NAVY)
    txt(ax, 57, 96.6, "«system»", fs=7, color=GREY)

    ucs = [("UC-01", "Login & Logout", 91),
           ("UC-02", "Lihat Dashboard", 84),
           ("UC-03", "Catat Barang Masuk", 77),
           ("UC-04", "Catat Barang Keluar", 70),
           ("UC-05", "Pindahkan Stok", 63),
           ("UC-06", "Sesuaikan Stok (Opname)", 56),
           ("UC-07", "Lihat & Ekspor Laporan", 49),
           ("UC-08", "Chat Asisten AI", 42),
           ("UC-09", "Kelola Master Barang", 35),
           ("UC-10", "Kelola Kategori & Rak", 28),
           ("UC-11", "Kelola Pengguna & Peran", 21)]
    pos = {}
    for code, name, y in ucs:
        ellipse(ax, 50, y, 36, 5.6, "%s   %s" % (code, name), fs=6.6)
        pos[code] = (50, y, 36, 5.6)

    # aktor utama
    actor(ax, 11, 91, "Operator")
    actor(ax, 11, 52.5, "Supervisor")
    actor(ax, 11, 24.5, "Admin")

    # generalisasi: Admin ⊂ Supervisor ⊂ Operator
    arrow(ax, (11, 30.3), (11, 48.4), color=GREY, style="-|>", lw=1.0, ms=9)
    arrow(ax, (11, 58.3), (11, 83.6), color=GREY, style="-|>", lw=1.0, ms=9)

    def assoc(code, ax_, ay_, ty=None, col=EDGE):
        cx, cy, w, h = pos[code]
        ty = cy if ty is None else ty
        p = ell_edge(cx, cy, w, h, ax_, ty)
        arrow(ax, (ax_, ay_), p, color=col, style="-", lw=0.8, shrink=0.6)

    for i, code in enumerate(["UC-01", "UC-02", "UC-03", "UC-04", "UC-05",
                              "UC-06", "UC-08"]):
        assoc(code, 12.4 + 0.28 * i, 91)
    assoc("UC-07", 12.4, 52.5)
    for i, code in enumerate(["UC-09", "UC-10", "UC-11"]):
        assoc(code, 12.4 + 0.3 * i, 24.5)

    # UC-12 (include) + relasi include dari 4 use case transaksi
    ellipse(ax, 79, 66.5, 15, 16, "UC-12\nKirim Notifikasi\nWhatsApp", fs=6.0,
            fc=AMBER_L, ec=AMBER, color="#7C2D12")
    for code in ["UC-03", "UC-04", "UC-05", "UC-06"]:
        cx, cy, w, h = pos[code]
        p = ell_edge(79, 66.5, 15, 16, 68, cy)
        arrow(ax, (cx + w / 2.0, cy), p, color=AMBER, style="-|>", lw=0.8,
              ls="--", ms=6, shrink=0.8)
    txt(ax, 69.9, 66.5, "«include»", fs=5.6, color=AMBER, rot=90,
        bbox=dict(boxstyle="square,pad=0.12", fc="white", ec="none"))

    # aktor sekunder
    actor(ax, 94.5, 66.5, "WhatsApp\nGateway", fs=6.6)
    actor(ax, 94.5, 42, "Google\nGemini", fs=6.6)
    arrow(ax, (92.0, 66.5), (86.8, 66.5), color=TEAL, style="-|>", lw=0.8,
          ls="--", ms=7)
    arrow(ax, (92.0, 42.0), (68.3, 42.0), color=TEAL, style="-|>", lw=0.8,
          ls="--", ms=7)

    save(fig, "fig_usecase.png")


# ======================================================================= ERD
def ent(ax, x, y, name, rows, w=19.0, fs=5.9, head=TEAL_L, ec=NAVY, fc=WHITE,
        lh=2.4, hh=4.0):
    h = hh + lh * len(rows) + 0.8
    ax.add_patch(Rectangle((x - w / 2, y - h / 2), w, h, facecolor=fc,
                           edgecolor=ec, linewidth=0.9, zorder=3))
    ax.add_patch(Rectangle((x - w / 2, y + h / 2 - hh), w, hh, facecolor=head,
                           edgecolor=ec, linewidth=0.9, zorder=4))
    fsh = min(6.7, (w - 1.2) / (0.131 * max(len(name), 1)))
    txt(ax, x, y + h / 2 - hh / 2, name, fs=fsh, bold=True, color=NAVY)
    yy = y + h / 2 - hh
    for r in rows:
        yy -= lh
        key = r.startswith("PK") or r.startswith("FK")
        txt(ax, x - w / 2 + 0.9, yy + lh / 2, r, fs=fs, ha="left",
            color=BLUE if key else INK)
    return dict(x=x, y=y, w=w, h=h, top=y + h / 2, bot=y - h / 2,
                left=x - w / 2, right=x + w / 2)


def erd():
    H = 90.0
    fig, ax = new_fig_u(H)
    y1, y2, y3 = 76.0, 47.0, 18.0
    xs = [11, 36, 62, 88]

    roles = ent(ax, xs[0], y1, "roles",
                ["PK id", "name", "description"])
    users = ent(ax, xs[1], y1, "users",
                ["PK id", "FK role_id", "username (UQ)", "password",
                 "email (UQ)", "status"])
    trx = ent(ax, xs[2], y1, "transaksi",
              ["PK id", "trx_number (UQ)", "type", "FK user_id",
               "supplier_name", "recipient_name", "trx_date"])
    tdet = ent(ax, xs[3], y1, "transaksi_detail",
               ["PK id", "FK transaksi_id", "FK barang_id", "FK lokasi_id",
                "FK lokasi_tujuan_id", "qty"])

    log = ent(ax, xs[0], y2, "log_aktivitas",
              ["PK id", "FK user_id", "action", "module", "description",
               "ip_address"])
    brg = ent(ax, xs[1], y2, "barang",
              ["PK id", "code (UQ)", "name", "FK kategori_id", "unit",
               "min_stock", "status"])
    stok = ent(ax, xs[2], y2, "stok",
               ["PK id", "FK barang_id", "FK lokasi_id", "qty",
                "last_updated", "UQ(barang,lokasi)"])
    lok = ent(ax, xs[3], y2, "lokasi",
              ["PK id", "code (UQ)", "rack", "level", "posisi",
               "kapasitas", "status"])

    kat = ent(ax, xs[0], y3, "kategori", ["PK id", "name", "description"])
    setg = ent(ax, xs[1], y3, "app_settings",
               ["PK key_name", "value", "updated_at"])
    walog = ent(ax, xs[2], y3, "whatsapp_logs",
                ["PK id", "recipient", "message_type", "status"])
    rtn = ent(ax, xs[3], y3, "realtime_notifications",
              ["PK id", "trx_id", "trx_number", "title", "message",
               "is_read"], head=AMBER_L, ec=AMBER)

    # --- relasi horizontal (1 : N)
    def hrel(a, b, y, la="1", lb="N"):
        poly(ax, [(a["right"], y), (b["left"], y)], color=GREY, lw=0.9)
        card(ax, a["right"] + 1.5, y + 1.6, la)
        card(ax, b["left"] - 1.5, y + 1.6, lb)

    hrel(roles, users, y1)
    hrel(users, trx, y1)
    hrel(trx, tdet, y1)
    hrel(brg, stok, y2)
    hrel(stok, lok, y2, "N", "1")

    # --- relasi vertikal / elbow
    # users(1) - log(N)
    poly(ax, [(users["left"] + 3, users["bot"]), (log["right"], log["top"])],
         color=GREY, lw=0.9)
    card(ax, users["left"] + 2.4, users["bot"] - 1.8, "1")
    card(ax, log["right"] + 1.4, log["top"] + 1.6, "N")

    # kategori(1) - barang(N)
    poly(ax, [(kat["x"], kat["top"]), (brg["x"], brg["bot"])],
         color=GREY, lw=0.9)
    card(ax, kat["x"] + 1.5, kat["top"] + 1.7, "1")
    card(ax, brg["x"] - 1.5, brg["bot"] - 1.7, "N")

    # barang(1) - transaksi_detail(N)  : elbow lewat celah antar baris
    ymid = (brg["top"] + trx["bot"]) / 2.0 + 0.4
    poly(ax, [(brg["x"] + 3, brg["top"]), (brg["x"] + 3, ymid),
              (tdet["x"] - 4, ymid), (tdet["x"] - 4, tdet["bot"])],
         color=GREY, lw=0.9)
    card(ax, brg["x"] + 1.6, brg["top"] + 1.7, "1")
    card(ax, tdet["x"] - 5.4, tdet["bot"] - 1.7, "N")

    # lokasi(1) - transaksi_detail(N)
    poly(ax, [(lok["x"], lok["top"]), (lok["x"], tdet["bot"])],
         color=GREY, lw=0.9)
    card(ax, lok["x"] - 1.5, lok["top"] + 1.7, "1")
    card(ax, lok["x"] + 1.6, tdet["bot"] - 1.7, "N")

    save(fig, "fig_erd.png")


# ============================================================ ACTIVITY BISNIS
def activity_business():
    H = 124.0
    fig, ax = new_fig_u(H)
    cx = 50.0
    y = H - 6.0

    box(ax, cx, y, 12, 4.2, "Mulai", fc=NAVY, ec=NAVY, color=WHITE, fs=7.6,
        bold=True)
    steps = [
        (y - 10, "Operator membuka menu\nBarang Masuk", TEAL_L, TEAL),
        (y - 19, "Sistem memuat form:\nsupplier, barang, rak, qty", WHITE, NAVY),
        (y - 28, "Operator mengisi &\nmengirim form", TEAL_L, TEAL),
        (y - 37, "Validasi input\n(barang aktif, rak aktif, qty > 0)",
         WHITE, NAVY),
    ]
    prev = (cx, y - 2.1)
    for yy, t, fc, ec in steps:
        box(ax, cx, yy, 34, 6.0, t, fc=fc, ec=ec, fs=7.2)
        arrow(ax, prev, (cx, yy + 3.0), color=NAVY2, lw=0.9)
        prev = (cx, yy - 3.0)

    diamond(ax, cx, y - 46.5, 16, 7.4, "valid?", fs=7.4)
    arrow(ax, prev, (cx, y - 42.8), color=NAVY2, lw=0.9)

    box(ax, 84, y - 46.5, 22, 6.0, "Tampilkan pesan\nerror & pertahankan\nisian",
        fc=ROSE_L, ec=ROSE, fs=7.0)
    arrow(ax, (cx + 8, y - 46.5), (73, y - 46.5), color=ROSE, lw=0.9)
    txt(ax, 69, y - 44.6, "tidak", fs=7, color=ROSE, bold=True)
    poly(ax, [(95, y - 46.5), (97.5, y - 46.5), (97.5, y - 28)],
         color=ROSE, lw=0.9, ls="--")
    arrow(ax, (97.5, y - 28), (67.6, y - 28), color=ROSE, lw=0.9, ls="--",
          ms=7)
    txt(ax, 90, y - 27.0, "kembali ke form", fs=6.4, color=ROSE, italic=True)

    box(ax, cx, y - 56, 34, 6.4,
        "BEGIN TRANSACTION\n(nomor TRX-IN-YYYYMMDD-NNNN)", fc=BLUE_L,
        ec=BLUE, fs=6.8)
    arrow(ax, (cx, y - 50.2), (cx, y - 52.8), color=NAVY2, lw=0.9)
    txt(ax, cx + 3.4, y - 51.4, "ya", fs=7, color=TEAL, bold=True)

    box(ax, cx, y - 65, 34, 6.0,
        "INSERT transaksi + transaksi_detail\n(stok di rak tujuan bertambah)",
        fc=WHITE, ec=NAVY, fs=7.2)
    arrow(ax, (cx, y - 59.2), (cx, y - 62.0), color=NAVY2, lw=0.9)

    box(ax, cx, y - 73.6, 34, 5.6,
        "Hitung ulang status rak (kosong / terisi)", fc=WHITE, ec=NAVY, fs=7.2)
    arrow(ax, (cx, y - 68.0), (cx, y - 70.8), color=NAVY2, lw=0.9)

    box(ax, cx, y - 82.2, 34, 5.6, "COMMIT transaksi basis data", fc=BLUE_L,
        ec=BLUE, fs=7.2, bold=True)
    arrow(ax, (cx, y - 76.4), (cx, y - 79.4), color=NAVY2, lw=0.9)

    ax.add_patch(Rectangle((cx - 17, y - 87.6), 34, 0.9, facecolor=NAVY,
                           edgecolor=NAVY, zorder=4))
    arrow(ax, (cx, y - 85.0), (cx, y - 87.6), color=NAVY2, lw=0.9)
    box(ax, 31, y - 94.5, 26, 5.4, "Kirim notifikasi\nWhatsApp + realtime",
        fc=AMBER_L, ec=AMBER, fs=7.0)
    box(ax, 69, y - 94.5, 26, 5.4, "Tampilkan bukti\ntransaksi (pop-up)",
        fc=TEAL_L, ec=TEAL, fs=7.0)
    arrow(ax, (31, y - 87.6), (31, y - 91.8), color=NAVY2, lw=0.9)
    arrow(ax, (69, y - 87.6), (69, y - 91.8), color=NAVY2, lw=0.9)
    ax.add_patch(Rectangle((cx - 17, y - 98.4), 34, 0.9, facecolor=NAVY,
                           edgecolor=NAVY, zorder=4))
    arrow(ax, (31, y - 97.2), (31, y - 98.4), color=NAVY2, lw=0.9)
    arrow(ax, (69, y - 97.2), (69, y - 98.4), color=NAVY2, lw=0.9)

    box(ax, cx, y - 104.5, 12, 4.2, "Selesai", fc=NAVY, ec=NAVY, color=WHITE,
        fs=7.6, bold=True)
    arrow(ax, (cx, y - 98.4), (cx, y - 102.4), color=NAVY2, lw=0.9)

    save(fig, "fig_activity_business.png")


# =============================================================== ACTIVITY AI
def activity_ai():
    H = 116.0
    fig, ax = new_fig_u(H)
    cx = 50.0
    y = H - 5.0
    yfin = y - 98.5

    box(ax, cx, y, 30, 4.2, "Pengguna mengirim pesan", fc=NAVY, ec=NAVY,
        color=WHITE, fs=7.4, bold=True)
    prev = (cx, y - 2.1)

    box(ax, cx, y - 11, 30, 5.4, "Sanitasi input + filter topik", fc=TEAL_L,
        ec=TEAL, fs=7.2)
    arrow(ax, prev, (cx, y - 8.3), color=NAVY2, lw=0.9)
    prev = (cx, y - 13.7)

    diamond(ax, cx, y - 21, 17, 7.0, "di luar domain\npergudangan?", fs=6.8)
    arrow(ax, prev, (cx, y - 17.5), color=NAVY2, lw=0.9)

    box(ax, 84, y - 21, 22, 5.6, "Tolak sopan +\nsaran perintah valid",
        fc=ROSE_L, ec=ROSE, fs=7.0)
    arrow(ax, (cx + 8.5, y - 21), (73, y - 21), color=ROSE, lw=0.9)
    txt(ax, 68, y - 19.3, "ya", fs=7, color=ROSE, bold=True)

    diamond(ax, cx, y - 31, 18, 7.2, "perintah SQL\nlangsung?", fs=6.8)
    arrow(ax, (cx, y - 24.5), (cx, y - 27.4), color=NAVY2, lw=0.9)
    txt(ax, cx + 2.2, y - 26.2, "tidak", fs=6.8, color=GREY)

    box(ax, 16, y - 31, 24, 5.6, "Read-only guard:\nprefix SELECT/SHOW +\nblokir DDL/DML",
        fc=BLUE_L, ec=BLUE, fs=6.8)
    arrow(ax, (cx - 9, y - 31), (28, y - 31), color=BLUE, lw=0.9)
    txt(ax, 32, y - 29.3, "ya", fs=7, color=BLUE, bold=True)

    box(ax, cx, y - 40.5, 34, 5.6, "Bangun snapshot data\n+ panggil Gemini API",
        fc=AMBER_L, ec=AMBER, fs=7.2)
    arrow(ax, (cx, y - 34.6), (cx, y - 37.7), color=NAVY2, lw=0.9)

    diamond(ax, cx, y - 50.5, 20, 7.4, "JSON intent\nvalid?", fs=6.8)
    arrow(ax, (cx, y - 43.3), (cx, y - 46.8), color=NAVY2, lw=0.9)

    box(ax, 84, y - 50.5, 22, 5.6, "Fallback: rule-based\nregex intent engine",
        fc=GREY_L, ec=GREY, fs=6.8)
    arrow(ax, (cx + 10, y - 50.5), (73, y - 50.5), color=GREY, lw=0.9)
    txt(ax, 70, y - 48.8, "tidak", fs=6.8, color=GREY, bold=True)

    diamond(ax, cx, y - 60.5, 18, 7.0, "intent\ndikenali?", fs=6.8)
    arrow(ax, (cx, y - 54.2), (cx, y - 57.0), color=NAVY2, lw=0.9)

    box(ax, 15, y - 60.5, 22, 5.6, "Pencarian langsung\n(nama barang /\nkode rak) → kartu bantuan",
        fc=GREY_L, ec=GREY, fs=6.6)
    arrow(ax, (cx - 9, y - 60.5), (26, y - 60.5), color=GREY, lw=0.9)
    txt(ax, 30, y - 58.8, "tidak", fs=6.8, color=GREY, bold=True)

    box(ax, cx, y - 70, 34, 5.6, "Validasi entitas + hitung dampak stok",
        fc=TEAL_L, ec=TEAL, fs=7.2)
    arrow(ax, (cx, y - 64.0), (cx, y - 67.2), color=NAVY2, lw=0.9)

    diamond(ax, cx, y - 80, 20, 7.4, "butuh konfirmasi\n(HITL)?", fs=6.8)
    arrow(ax, (cx, y - 72.8), (cx, y - 76.3), color=NAVY2, lw=0.9)

    box(ax, 84, y - 80, 22, 6.2, "Tampilkan kartu\nkonfirmasi → tunggu\ntombol pengguna",
        fc=BLUE_L, ec=BLUE, fs=6.6)
    arrow(ax, (cx + 10, y - 80), (73, y - 80), color=BLUE, lw=0.9)
    txt(ax, 70, y - 78.3, "ya", fs=7, color=BLUE, bold=True)
    arrow(ax, (84, y - 83.1), (84, y - 87.0), color=BLUE, lw=0.9, ls="--")
    poly(ax, [(84, y - 87.0), (84, y - 90), (67.5, y - 90)], color=BLUE,
         lw=0.9, ls="--")
    arrow(ax, (75, y - 90), (67.5, y - 90), color=BLUE, lw=0.9, ls="--", ms=7)

    box(ax, cx, y - 90, 34, 5.6, "Eksekusi aksi (transaksi DB) + catat log",
        fc=WHITE, ec=NAVY, fs=7.2)
    arrow(ax, (cx, y - 83.7), (cx, y - 87.2), color=NAVY2, lw=0.9)

    box(ax, cx, yfin, 34, 5.6, "Kembalikan respons terstruktur\n(tabel / kartu / teks)",
        fc=NAVY, ec=NAVY, color=WHITE, fs=7.2, bold=True)
    arrow(ax, (cx, y - 92.8), (cx, yfin + 2.8), color=NAVY2, lw=0.9)

    # jalur gabung (merge) cabang kiri & kanan menuju kotak respons
    poly(ax, [(95, y - 21), (97.6, y - 21), (97.6, yfin), (67, yfin)],
         color="#94A3B8", lw=0.8, ls="--")
    poly(ax, [(95, y - 50.5), (97.6, y - 50.5)], color="#94A3B8", lw=0.8,
         ls="--")
    poly(ax, [(4, y - 31), (2.4, y - 31), (2.4, yfin), (33, yfin)],
         color="#94A3B8", lw=0.8, ls="--")
    poly(ax, [(4, y - 60.5), (2.4, y - 60.5)], color="#94A3B8", lw=0.8, ls="--")

    save(fig, "fig_activity_ai.png")


# ================================================================ CLASS DIAGRAM
def class_box(ax, x, y, w, name, attrs, methods, stereo=None, fc=WHITE, ec=NAVY,
              head=TEAL_L, fs=6.0, lh=2.5):
    hh = 4.2 + (2.0 if stereo else 0)
    h = hh + lh * (len(attrs) + len(methods)) + 1.6
    top = y + h / 2
    ax.add_patch(Rectangle((x - w / 2, y - h / 2), w, h, facecolor=fc,
                           edgecolor=ec, linewidth=0.9, zorder=3))
    ax.add_patch(Rectangle((x - w / 2, top - hh), w, hh, facecolor=head,
                           edgecolor=ec, linewidth=0.9, zorder=4))
    if stereo:
        txt(ax, x, top - 1.55, stereo, fs=fs - 0.5, color=GREY, italic=True)
        txt(ax, x, top - 3.35, name, fs=fs + 0.5, bold=True, color=NAVY)
    else:
        txt(ax, x, top - 2.1, name, fs=fs + 0.5, bold=True, color=NAVY)
    yy = top - hh
    for a in attrs:
        yy -= lh
        txt(ax, x - w / 2 + 0.9, yy + lh / 2, a, fs=fs, ha="left", color=INK)
    ax.plot([x - w / 2, x + w / 2], [yy, yy], color=ec, linewidth=0.7, zorder=4)
    for m in methods:
        yy -= lh
        txt(ax, x - w / 2 + 0.9, yy + lh / 2, m, fs=fs, ha="left", color=BLUE)
    return dict(x=x, y=y, w=w, h=h, top=top, bot=y - h / 2,
                left=x - w / 2, right=x + w / 2)


def classdiagram():
    H = 84.0
    fig, ax = new_fig_u(H)
    y1, y2, y3 = 70.6, 42.3, 14.25
    xs = [11, 37, 63, 88]

    router = class_box(ax, xs[0], y1, 19, "Router",
                       ["- controller : string", "- method : string"],
                       ["+ dispatch(url) : void", "+ verifyCsrf() : void"])
    tc = class_box(ax, xs[1], y1, 23, "TransaksiController",
                   ["- model : Transaksi"],
                   ["+ masuk() : void", "+ store() : void",
                    "+ storeKeluar() : void", "+ storeTransfer() : void",
                    "+ pollNotifications()"])
    aic = class_box(ax, xs[2], y1, 23, "AichatController",
                    ["- engine : WarehouseAiEngine"],
                    ["+ index() : void", "+ message() : json",
                     "+ executeAction() : json"])
    sc = class_box(ax, xs[3], y1, 19, "StokController",
                   ["- model : Stok"],
                   ["+ index() : void", "+ adjust() : void",
                    "+ apiAdjustStok() : json"])

    usr = class_box(ax, xs[0], y2, 19, "User", ["- db : PDO"],
                    ["+ findByUsername()", "+ verifyPassword()",
                     "+ getAllRoles()"])
    trx = class_box(ax, xs[1], y2, 23, "Transaksi", ["- db : PDO"],
                    ["+ create() : int", "+ getRiwayat() : array",
                     "+ generateNumber() : string"])
    stk = class_box(ax, xs[2], y2, 23, "Stok", ["- db : PDO"],
                    ["+ adjustStok() : bool", "+ getStockReport() : array",
                     "+ recalcRackStatus() : void"])
    brg = class_box(ax, xs[3], y2, 19, "Barang", ["- db : PDO"],
                    ["+ getAllPaginated()", "+ isCodeUnique()",
                     "+ getLocations()"])

    db = class_box(ax, xs[0], y3, 19, "Database",
                   ["- instance : Database"], ["+ getConnection() : PDO"],
                   stereo="«singleton»", head=GREY_L, ec=GREY)
    eng = class_box(ax, xs[1], y3, 23, "WarehouseAiEngine",
                    ["- db : PDO", "- geminiKey : string"],
                    ["+ process(msg, ctx) : array",
                     "+ executeInbound() : array",
                     "+ executeSafeSqlQuery()"],
                    stereo="«service»", head=AMBER_L, ec=AMBER)
    wa = class_box(ax, xs[2], y3, 23, "WhatsAppHelper",
                   ["- provider : string"],
                   ["+ sendMessage() : bool", "+ normalizePhone() : string",
                    "+ parseRecipients() : array"],
                   stereo="«utility»", head=AMBER_L, ec=AMBER)
    nf = class_box(ax, xs[3], y3, 19, "NotificationHelper", ["- db : PDO"],
                   ["+ record() : int", "+ pollNew() : array",
                    "+ markAllRead() : int"],
                   stereo="«utility»", head=AMBER_L, ec=AMBER)

    def dep(p1, p2, color=TEAL, rad=0.0):
        arrow(ax, p1, p2, color=color, style="-|>", lw=0.8, ls="--", ms=7,
              rad=rad, shrink=1.0)

    dep((router["right"], y1), (tc["left"], y1))
    dep((tc["x"], tc["bot"]), (trx["x"], trx["top"]))
    poly(ax, [(aic["x"] - 5, aic["bot"]), (aic["x"] - 5, 55.0), (50.0, 55.0),
              (50.0, 26.4)], color=TEAL, lw=0.8, ls="--")
    arrow(ax, (50.0, 26.4), (47.0, 24.8), color=TEAL, style="-|>", lw=0.8,
          ls="--", ms=7, shrink=0.4)
    dep((sc["x"] - 2, sc["bot"]), (stk["x"] + 3, stk["top"]))
    dep((eng["x"] + 8, eng["top"]), (stk["x"] - 3, stk["bot"]))
    dep((stk["x"] + 5, stk["bot"]), (nf["x"] - 3, nf["top"]))
    dep((usr["x"], usr["bot"]), (db["x"], db["top"]), color=GREY)
    dep((trx["x"] - 7, trx["bot"]), (db["x"] + 5, db["top"]), color=GREY)

    save(fig, "fig_class.png")


# ============================================================ SEQUENCE TRANSAKSI
def seq_transaction():
    H = 90.0
    fig, ax = new_fig_u(H)
    top = H - 7.0
    xs = [7, 21.3, 35.7, 50, 64.3, 78.7, 93]
    labels = ["Operator\n(Browser)", "Router", "Transaksi\nController",
              "Transaksi\n«model»", "Stok\n«model»", "Notification\nHelper",
              "MySQL"]
    for x, l in zip(xs, labels):
        lifeline(ax, x, top, 12.0, l, fs=6.2)

    ypos = {}
    cur = [74.0]

    def m(num, i, j, text, style="-|>", ls="-", color=NAVY2):
        ypos[num] = cur[0]
        msg(ax, xs[i], xs[j], cur[0], text, num=num, style=style, ls=ls,
            color=color)
        cur[0] -= 3.55

    def s(i, text):
        selfmsg(ax, xs[i], cur[0], text)
        cur[0] -= 4.4

    m(1, 0, 1, "POST /transaksi/store")
    m(2, 1, 2, "verifyCsrf() → dispatch")
    m(3, 2, 3, "create($data)")
    s(3, "BEGIN")
    m(4, 3, 6, "INSERT transaksi")
    m(5, 3, 6, "INSERT transaksi_detail")
    m(6, 3, 4, "tambahStok(barang, rak, qty)")
    m(7, 4, 6, "UPDATE stok / INSERT baru")
    s(3, "COMMIT")
    m(8, 3, 2, "return trx_id + nomor", ls="--", color=TEAL)
    m(9, 2, 5, "record(type, trxNumber, ...)")
    m(10, 5, 6, "INSERT notifikasi realtime")
    m(11, 2, 0, "render bukti transaksi", ls="--", color=TEAL)
    m(12, 0, 1, "GET pollNotifications")
    m(13, 1, 2, "pollNotifications()")
    m(14, 2, 5, "pollNew(sinceId)")
    m(15, 5, 6, "SELECT ... WHERE id > ?")
    m(16, 5, 0, "JSON notifikasi (toast + badge)", ls="--", color=TEAL)

    activation(ax, xs[2], ypos[2], ypos[13])
    activation(ax, xs[3], ypos[3], ypos[8])
    activation(ax, xs[4], ypos[6], ypos[7])
    activation(ax, xs[5], ypos[9], ypos[10])

    save(fig, "fig_sequence_transaction.png")


# ================================================================= SEQUENCE AI
def seq_ai():
    H = 86.0
    fig, ax = new_fig_u(H)
    top = H - 7.0
    xs = [7.5, 23, 40.5, 58, 76, 92.5]
    labels = ["Pengguna\n(Browser)", "Aichat\nController",
              "Warehouse\nAiEngine", "Gemini\nAPI", "MySQL", "View"]
    for x, l in zip(xs, labels):
        lifeline(ax, x, top, 7.0, l, fs=6.2, w=13.5)

    ypos = {}
    cur = [70.0]

    def m(num, i, j, text, style="-|>", ls="-", color=NAVY2):
        ypos[num] = cur[0]
        msg(ax, xs[i], xs[j], cur[0], text, num=num, style=style, ls=ls,
            color=color)
        cur[0] -= 3.6

    def s(i, text):
        selfmsg(ax, xs[i], cur[0], text)
        cur[0] -= 4.4

    m(1, 0, 1, "POST /aichat/message {message}")
    m(2, 1, 2, "process(message, userContext)")
    s(2, "filter topik + deteksi intent")
    m(3, 2, 4, "buildSnapshot(): SELECT barang, rak")
    m(4, 2, 3, "POST generateContent(prompt)")
    m(5, 3, 2, "JSON {intent, parameters}", ls="--", color=TEAL)
    s(2, "parse + validasi entitas")
    m(6, 2, 4, "executeSafeSqlQuery / cek stok")
    m(7, 4, 2, "result rows", ls="--", color=TEAL)
    s(2, "susun kartu konfirmasi / tabel")
    m(8, 2, 1, "array respons terstruktur", ls="--", color=TEAL)
    m(9, 1, 5, "render komponen UI")
    m(10, 5, 0, "HTML / JSON ditampilkan", ls="--", color=TEAL)
    m(11, 0, 1, "POST /aichat/executeAction")
    m(12, 1, 2, "execute*($params)")
    m(13, 2, 4, "BEGIN → INSERT/UPDATE → COMMIT")
    m(14, 2, 0, "hasil eksekusi + log aktivitas", ls="--", color=TEAL)

    activation(ax, xs[1], ypos[2], ypos[12])
    activation(ax, xs[2], ypos[2], ypos[14])
    activation(ax, xs[3], ypos[4], ypos[5])

    save(fig, "fig_sequence_ai.png")


# ================================================================== WIREFRAME
def wireframe():
    fig = plt.figure(figsize=(WIDTH_IN, 8.55), dpi=DPI)
    ax = fig.add_axes([0, 0, 1, 1])
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 136)
    ax.set_aspect("equal")
    ax.axis("off")

    def panel(x, y, w, h, title, sub):
        ax.add_patch(FancyBboxPatch((x, y), w, h,
                                    boxstyle="round,pad=0,rounding_size=1.4",
                                    facecolor=WHITE, edgecolor=EDGE,
                                    linewidth=1.0))
        ax.add_patch(Rectangle((x, y + h - 5.2), w, 5.2, facecolor=NAVY,
                               edgecolor=NAVY))
        for i, c in enumerate(["#F87171", "#FBBF24", "#34D399"]):
            ax.add_patch(Circle((x + 2.2 + i * 2.0, y + h - 2.6), 0.62,
                                facecolor=c, edgecolor="none"))
        txt(ax, x + w / 2 + 2.0, y + h - 2.6, title, fs=6.6, color=WHITE,
            bold=True)
        txt(ax, x + w / 2, y + h - 7.4, sub, fs=6.8, color=GREY, bold=True)

    def tline(x, y, w, h=1.1, c="#E2E8F0"):
        ax.add_patch(FancyBboxPatch((x, y), w, h,
                                    boxstyle="round,pad=0,rounding_size=0.5",
                                    facecolor=c, edgecolor="none"))

    def block(x, y, w, h, label="", fc=GREY_L, ec="#DCE3EC", fs=6.2,
              color=GREY):
        ax.add_patch(FancyBboxPatch((x, y), w, h,
                                    boxstyle="round,pad=0,rounding_size=1.0",
                                    facecolor=fc, edgecolor=ec, linewidth=0.8))
        if label:
            txt(ax, x + w / 2, y + h / 2, label, fs=fs, color=color, lsp=1.2)

    # panel 1: dashboard
    px, py, pw, ph = 2, 70, 96, 62
    panel(px, py, pw, ph, "SmartWarehouse", "Dashboard")
    ax.add_patch(Rectangle((px, py), 18, ph - 5.2, facecolor="#F8FAFC",
                           edgecolor=EDGE, linewidth=0.8))
    for i in range(7):
        tline(px + 2.2, py + ph - 13 - i * 4.2, 13, 1.0,
              "#CBD5E1" if i else TEAL)
    for i in range(4):
        block(px + 22 + i * 18.6, py + ph - 24, 16.6, 10.5,
              ["Barang", "Stok", "Rak", "Masuk"][i], fc="#F1F5F9", fs=5.8)
        tline(px + 23.4 + i * 18.6, py + ph - 17.5, 8, 2.6, NAVY)
    block(px + 22, py + ph - 46, 44, 18, "Grafik tren 7 hari (Chart.js)",
          fc=WHITE, fs=6.0)
    block(px + 68, py + ph - 46, 28, 18, "Peringatan stok minimum",
          fc=ROSE_L, ec="#F5B7C4", fs=6.0, color=ROSE)
    block(px + 22, py + 2.6, 74, 9.5, "5 transaksi terbaru", fc=WHITE, fs=6.0)
    txt(ax, px + pw / 2, py - 4.0,
        "(a) Dashboard — kartu metrik, grafik, peringatan", fs=6.8,
        color=NAVY2, bold=True)

    # panel 2: form transaksi
    px, py = 2, 6
    panel(px, py, 46, 58, "Barang Masuk", "Form transaksi")
    yy = py + 40
    for lbl, wdt in (("Supplier", 40), ("Barang", 40), ("Rak tujuan", 40),
                     ("Qty", 18)):
        txt(ax, px + 3, yy + 3.2, lbl, fs=5.8, color=GREY, ha="left")
        ax.add_patch(FancyBboxPatch((px + 3, yy), wdt, 5.2,
                                    boxstyle="round,pad=0,rounding_size=0.7",
                                    facecolor=WHITE, edgecolor="#CBD5E1",
                                    linewidth=0.8))
        yy -= 8.4
    block(px + 3, py + 3.0, 18, 6.0, "Simpan", fc=TEAL, ec=TEAL, fs=6.0,
          color=WHITE)
    block(px + 24, py + 3.0, 18, 6.0, "Batal", fc=WHITE, fs=6.0)
    txt(ax, px + 23, py - 4.0, "(b) Form transaksi — validasi & konfirmasi",
        fs=6.8, color=NAVY2, bold=True)

    # panel 3: AI chat
    px = 52
    panel(px, py, 46, 58, "Asisten AI", "Chat")
    bubble = [
        (px + 22, py + 40, 21, 8, "Berapa stok barang A.1.05?", WHITE, EDGE),
        (px + 3, py + 28, 24, 11, "Stok 2011100049 di rak\nA.1.05 = 15 unit",
         TEAL_L, TEAL),
        (px + 20, py + 16, 23, 8, "Pindahkan 5 ke A.1.06", WHITE, EDGE),
        (px + 3, py + 4, 27, 11, "Kartu konfirmasi:\nA.1.05 15→10 · A.1.06 +5",
         AMBER_L, AMBER),
    ]
    for bx, by, bw, bh, t, fc, ec in bubble:
        block(bx, by, bw, bh, t, fc=fc, ec=ec, fs=5.5,
              color=INK if fc == WHITE else (TEAL if fc == TEAL_L else "#7C2D12"))
    ax.add_patch(FancyBboxPatch((px + 3, py + 0.6), 34, 2.4,
                                boxstyle="round,pad=0,rounding_size=0.8",
                                facecolor=WHITE, edgecolor="#CBD5E1",
                                linewidth=0.8))
    txt(ax, px + 23, py - 4.0, "(c) Asisten AI — NLQ, tabel & kartu konfirmasi",
        fs=6.8, color=NAVY2, bold=True)

    save(fig, "fig_wireframe.png")


# ================================================================ ARSITEKTUR
def architecture():
    H = 80.0
    fig, ax = new_fig_u(H)

    layers = [
        ("PRESENTATION LAYER", H - 10, TEAL_L, TEAL,
         "Browser · Bootstrap 5.3 · Chart.js · Fetch API"),
        ("APPLICATION LAYER", H - 26, BLUE_L, BLUE,
         "Router · 12 Controller · CSRF & RBAC"),
        ("DOMAIN / SERVICE LAYER", H - 42, AMBER_L, AMBER,
         "10 Model · WarehouseAiEngine · WhatsAppHelper · NotificationHelper"),
        ("DATA LAYER", H - 58, GREY_L, GREY,
         "PDO (prepared statement, persistent) · MySQL 8.0"),
    ]
    for name, y, fc, ec, sub in layers:
        box(ax, 50, y, 88, 10.5, "", fc=fc, ec=ec, lw=1.1)
        txt(ax, 50, y + 2.4, name, fs=8.0, bold=True, color=ec)
        txt(ax, 50, y - 2.6, sub, fs=6.8, color=INK)

    for i in range(3):
        y1 = layers[i][1] - 5.25
        arrow(ax, (50, y1), (50, y1 - 5.5), color=NAVY2, lw=1.0,
              style="<|-|>", ms=9)

    box(ax, 18, H - 72, 32, 9.0, "Google Gemini API\n(HTTPS / JSON)", fc=WHITE,
        ec=TEAL, fs=7.2, lw=1.0)
    box(ax, 55, H - 72, 30, 9.0, "WhatsApp Gateway\n(Fonnte / Wablas / UltraMsg)",
        fc=WHITE, ec=TEAL, fs=7.0, lw=1.0)
    box(ax, 87, H - 72, 20, 9.0, "YouTube /\nGitHub Actions", fc=WHITE, ec=GREY,
        fs=6.8, lw=0.9)

    arrow(ax, (40, H - 63.4), (26, H - 67.4), color=TEAL, lw=0.9, ls="--",
          ms=8)
    arrow(ax, (58, H - 63.4), (58, H - 67.4), color=TEAL, lw=0.9, ls="--",
          ms=8)

    save(fig, "fig_architecture.png")


if __name__ == "__main__":
    usecase()
    activity_business()
    activity_ai()
    classdiagram()
    seq_transaction()
    seq_ai()
    erd()
    wireframe()
    architecture()
