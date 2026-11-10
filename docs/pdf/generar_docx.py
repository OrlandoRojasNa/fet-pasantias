"""Genera las versiones en Word de los manuales de producto de Pasantías FET.

Cada documento lleva la portada del producto, la ficha del documento, un índice que Word
actualiza al abrir el archivo, capítulos numerados, encabezado y pie de página, y contraportada.
Las imágenes de portada y contraportada las produce docs/pdf/generar_manuales.js, que debe
ejecutarse antes.

Requiere pandoc, python-docx y, para el diagrama, Node.js con Playwright y el paquete mermaid.
Uso: python3 docs/pdf/generar_docx.py
"""
import copy, os, re, subprocess, sys, tempfile
from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_TAB_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Mm, Pt, RGBColor

PRODUCTO = 'Pasantías FET'
VERSION = '2.0.0'
FECHA = 'Noviembre de 2026'
FECHA_CORTA = '10/11/2026'
EQUIPO = ['Orlando Rojas Narváez', 'Hernán Felipe Patiño Rico', 'Juan José Sabogal García']
CLIENTE = 'Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez'
MANUALES = [
    dict(md='docs/manual/MANUAL_USUARIO.md', docx='docs/word/Manual_de_Usuario_Pasantias_FET_v2.0.0.docx',
         codigo='PF-MU-200', titulo='Manual de usuario',
         publico='Estudiantes, empresas aliadas, docentes líderes de pasantías y administradores de la plataforma'),
    dict(md='docs/MANUAL_DESPLIEGUE.md', docx='docs/word/Manual_de_Despliegue_Pasantias_FET_v2.0.0.docx',
         codigo='PF-MD-200', titulo='Manual de despliegue',
         publico='Personal de sistemas e infraestructura responsable del servidor'),
]
FUENTE, MONO = 'Arial', 'Consolas'
VERDE, VERDE2, ORO, TINTA, GRIS = '143A21', '1F5430', 'C9962B', '24332A', '5F6F66'
MENTA, CEBRA, LINEA = 'E7F2EA', 'F5F9F6', 'D4E0D8'
AQUI = os.path.dirname(os.path.abspath(__file__))


# ---------------------------------------------------------------- Markdown

def preparar_md(md_path, tmp):
    texto = open(md_path, encoding='utf-8').read()
    n = 0

    def diagrama(m):
        nonlocal n
        n += 1
        mmd, png = os.path.join(tmp, f'diagrama{n}.mmd'), os.path.join(tmp, f'diagrama{n}.png')
        open(mmd, 'w', encoding='utf-8').write(m.group(1))
        subprocess.run(['node', os.path.join(AQUI, 'mermaid_png.js'), mmd, png], check=True)
        return f'![Diagrama]({png})'

    texto = re.sub(r'```mermaid\n(.*?)```', diagrama, texto, flags=re.S)
    # El título va en la portada; la introducción queda bajo "Acerca de este manual".
    texto = re.sub(r'^# .+\n', '## Acerca de este manual\n', texto, count=1, flags=re.M)
    # Leyendas de tablas y figuras en una sola línea: "Tabla 1. Título".
    texto = re.sub(r'^\*\*((?:Tabla|Figura) \d+)\*\*\n\n\*(.+?)\*[ \t]*$', r'**\1.** \2', texto, flags=re.M)
    # Numeración de capítulos y secciones, fuera de los bloques de código.
    partes = re.split(r'(```.*?```)', texto, flags=re.S)
    c = s = 0
    for i in range(0, len(partes), 2):
        lineas = partes[i].split('\n')
        for j, l in enumerate(lineas):
            if l.startswith('## '):
                c += 1; s = 0
                lineas[j] = f'## {c}. {l[3:]}'
            elif l.startswith('### '):
                s += 1
                lineas[j] = f'### {c}.{s} {l[4:]}'
        partes[i] = '\n'.join(lineas)
    salida = os.path.join(tmp, 'entrada.md')
    open(salida, 'w', encoding='utf-8').write(''.join(partes))
    return salida


# ---------------------------------------------------------------- utilidades XML

def color(h):
    return RGBColor.from_string(h)


def fuente(obj, size=None, mono=False, rgb=None, bold=None):
    f = obj.font
    nombre = MONO if mono else FUENTE
    f.name = nombre
    if size:
        f.size = Pt(size)
    if rgb:
        f.color.rgb = color(rgb)
    if bold is not None:
        f.bold = bold
    rpr = obj.element.get_or_add_rPr()
    rf = rpr.find(qn('w:rFonts'))
    if rf is None:
        rf = OxmlElement('w:rFonts'); rpr.append(rf)
    for a in ('w:ascii', 'w:hAnsi', 'w:cs', 'w:eastAsia'):
        rf.set(qn(a), nombre)
    for a in ('w:asciiTheme', 'w:hAnsiTheme', 'w:cstheme', 'w:eastAsiaTheme'):
        rf.attrib.pop(qn(a), None)


def sombra(padre_pr, relleno):
    for v in padre_pr.findall(qn('w:shd')):
        padre_pr.remove(v)
    s = OxmlElement('w:shd')
    s.set(qn('w:val'), 'clear'); s.set(qn('w:color'), 'auto'); s.set(qn('w:fill'), relleno)
    padre_pr.append(s)


def borde(padre, lado, val='single', sz=4, col=LINEA, espacio='0'):
    b = OxmlElement(f'w:{lado}')
    b.set(qn('w:val'), val); b.set(qn('w:sz'), str(sz)); b.set(qn('w:space'), espacio); b.set(qn('w:color'), col)
    padre.append(b)


def borde_parrafo(p, lado, col, sz=8, espacio='6'):
    ppr = p.get_or_add_pPr() if hasattr(p, 'get_or_add_pPr') else p._p.get_or_add_pPr()
    pb = ppr.find(qn('w:pBdr'))
    if pb is None:
        pb = OxmlElement('w:pBdr'); ppr.append(pb)
    borde(pb, lado, sz=sz, col=col, espacio=espacio)


def campo(parrafo, instr, rgb=GRIS, size=7.5, bold=False):
    run = parrafo.add_run()
    fuente(run, size, rgb=rgb, bold=bold)
    for tipo in ('begin', 'instr', 'separate', 'end'):
        if tipo == 'instr':
            el = OxmlElement('w:instrText'); el.set(qn('xml:space'), 'preserve'); el.text = f' {instr} '
        else:
            el = OxmlElement('w:fldChar'); el.set(qn('w:fldCharType'), tipo)
        run._r.append(el)
        if tipo == 'separate':
            t = OxmlElement('w:t'); t.text = '1'; run._r.append(t)


def texto(parrafo, t, size=None, rgb=None, bold=None, italic=None, mono=False):
    r = parrafo.add_run(t)
    fuente(r, size, mono=mono, rgb=rgb, bold=bold)
    if italic is not None:
        r.italic = italic
    return r


ORDEN_PPR = ['pStyle', 'keepNext', 'keepLines', 'pageBreakBefore', 'framePr', 'widowControl', 'numPr',
             'suppressLineNumbers', 'pBdr', 'shd', 'tabs', 'suppressAutoHyphens', 'kinsoku', 'wordWrap',
             'overflowPunct', 'topLinePunct', 'autoSpaceDE', 'autoSpaceDN', 'bidi', 'adjustRightInd', 'snapToGrid',
             'spacing', 'ind', 'contextualSpacing', 'mirrorIndents', 'suppressOverlap', 'jc', 'textDirection',
             'textAlignment', 'textboxTightWrap', 'outlineLvl', 'divId', 'cnfStyle', 'rPr', 'sectPr', 'pPrChange']
ORDEN_RPR = ['rStyle', 'rFonts', 'b', 'bCs', 'i', 'iCs', 'caps', 'smallCaps', 'strike', 'dstrike', 'outline',
             'shadow', 'emboss', 'imprint', 'noProof', 'snapToGrid', 'vanish', 'webHidden', 'color', 'spacing', 'w',
             'kern', 'position', 'sz', 'szCs', 'highlight', 'u', 'effect', 'bdr', 'shd', 'fitText', 'vertAlign',
             'rtl', 'cs', 'em', 'lang', 'eastAsianLayout', 'specVanish', 'oMath']
ORDEN_TBLPR = ['tblStyle', 'tblpPr', 'tblOverlap', 'bidiVisual', 'tblStyleRowBandSize', 'tblStyleColBandSize',
               'tblW', 'jc', 'tblCellSpacing', 'tblInd', 'tblBorders', 'shd', 'tblLayout', 'tblCellMar', 'tblLook',
               'tblCaption', 'tblDescription']
ORDEN_TCPR = ['cnfStyle', 'tcW', 'gridSpan', 'hMerge', 'vMerge', 'tcBorders', 'shd', 'noWrap', 'tcMar',
              'textDirection', 'tcFitText', 'vAlign', 'hideMark']
ORDEN_TRPR = ['cnfStyle', 'divId', 'gridBefore', 'gridAfter', 'wBefore', 'wAfter', 'cantSplit', 'trHeight',
              'tblHeader', 'tblCellSpacing', 'jc', 'hidden']
ORDEN_ESTILO = ['name', 'aliases', 'basedOn', 'next', 'link', 'autoRedefine', 'hidden', 'uiPriority', 'semiHidden',
                'unhideWhenUsed', 'qFormat', 'locked', 'personal', 'personalCompose', 'personalReply', 'rsid',
                'pPr', 'rPr', 'tblPr', 'trPr', 'tcPr', 'tblStylePr']
ORDEN_SECTPR = ['headerReference', 'footerReference', 'footnotePr', 'endnotePr', 'type', 'pgSz', 'pgMar',
                'paperSrc', 'pgBorders', 'lnNumType', 'pgNumType', 'cols', 'formProt', 'vAlign', 'noEndnote',
                'titlePg', 'textDirection', 'bidi', 'rtlGutter', 'docGrid', 'printerSettings', 'sectPrChange']


def reordenar(el, orden):
    pos = {qn('w:' + n): i for i, n in enumerate(orden)}
    hijos = list(el)
    hijos.sort(key=lambda h: pos.get(h.tag, len(orden)))
    for h in hijos:
        el.remove(h)
    for h in hijos:
        el.append(h)


def ordenar_xml(doc):
    raices = [doc.styles.element, doc.element.body]
    for sec in doc.sections:
        for hf in (sec.header, sec.footer):
            if not hf.is_linked_to_previous:
                raices.append(hf._element)
    for est in doc.styles.element.iter(qn('w:style')):
        reordenar(est, ORDEN_ESTILO)
    for raiz in raices:
        for tag, orden in (('w:pPr', ORDEN_PPR), ('w:tblPr', ORDEN_TBLPR), ('w:tcPr', ORDEN_TCPR),
                           ('w:trPr', ORDEN_TRPR), ('w:sectPr', ORDEN_SECTPR)):
            for el in raiz.iter(qn(tag)):
                reordenar(el, orden)
        for rpr in raiz.iter(qn('w:rPr')):
            rpr.text = None
            reordenar(rpr, ORDEN_RPR)
    for pgmar in doc.element.body.iter(qn('w:pgMar')):
        pgmar.set(qn('w:footer'), pgmar.get(qn('w:footer')) or '720')
        pgmar.set(qn('w:gutter'), '0')


# ---------------------------------------------------------------- estilos

def ajustar_estilos(doc):
    est = {s.name: s for s in doc.styles}
    for nombre in ('Normal', 'Body Text', 'First Paragraph', 'Compact', 'Block Text', 'Image Caption',
                   'Captioned Figure', 'Figure', 'Table Caption'):
        if nombre in est:
            e = est[nombre]
            fuente(e, 10, rgb=TINTA)
            pf = e.paragraph_format
            pf.line_spacing = 1.3; pf.space_before = Pt(0); pf.space_after = Pt(7); pf.first_line_indent = Pt(0)
    est['Compact'].paragraph_format.space_after = Pt(3)
    for nivel, size, rgb, antes, despues in ((1, 22, VERDE, 0, 14), (2, 21, VERDE, 0, 14), (3, 13, VERDE2, 14, 6),
                                             (4, 11, VERDE2, 10, 4)):
        e = est[f'Heading {nivel}']
        fuente(e, size, rgb=rgb, bold=True)
        e.font.italic = False
        pf = e.paragraph_format
        pf.space_before = Pt(antes); pf.space_after = Pt(despues); pf.keep_with_next = True
        pf.line_spacing = 1.1; pf.alignment = WD_ALIGN_PARAGRAPH.LEFT
    h2 = est['Heading 2']
    h2.paragraph_format.page_break_before = True
    borde_parrafo(h2.element, 'bottom', MENTA, sz=12, espacio='8')
    if 'Source Code' in est:
        e = est['Source Code']
        fuente(e, 8.5, mono=True, rgb=VERDE)
        pf = e.paragraph_format
        pf.line_spacing = 1.15; pf.space_before = Pt(2); pf.space_after = Pt(10)
        pf.left_indent = Pt(6); pf.right_indent = Pt(6)
        ppr = e.element.get_or_add_pPr()
        sombra(ppr, 'F1F6F2')
        pb = OxmlElement('w:pBdr')
        for lado in ('top', 'left', 'bottom', 'right'):
            borde(pb, lado, sz=4, col=LINEA, espacio='5')
        ppr.append(pb)
    if 'Verbatim Char' in est:
        fuente(est['Verbatim Char'], 9, mono=True, rgb=VERDE)
    if 'Hyperlink' in est:
        est['Hyperlink'].font.color.rgb = color('2F7A47')
        est['Hyperlink'].font.underline = False
    # Los tokens de resaltado de código de pandoc heredan el color del bloque.
    for s in doc.styles:
        if s.name.endswith('Tok'):
            fuente(s, None, mono=True)


def tabla_producto(tabla, ficha=False):
    tblpr = tabla._tbl.tblPr
    for v in tblpr.findall(qn('w:tblBorders')):
        tblpr.remove(v)
    bs = OxmlElement('w:tblBorders')
    for lado in ('top', 'left', 'bottom', 'right', 'insideH'):
        borde(bs, lado, sz=4, col=LINEA)
    borde(bs, 'insideV', val='nil')
    tblpr.append(bs)
    ancho = tblpr.find(qn('w:tblW'))
    if ancho is None:
        ancho = OxmlElement('w:tblW'); tblpr.append(ancho)
    ancho.set(qn('w:type'), 'pct'); ancho.set(qn('w:w'), '5000')
    mar = OxmlElement('w:tblCellMar')
    for lado, v in (('top', 70), ('left', 110), ('bottom', 70), ('right', 110)):
        m = OxmlElement(f'w:{lado}'); m.set(qn('w:w'), str(v)); m.set(qn('w:type'), 'dxa'); mar.append(m)
    for v in tblpr.findall(qn('w:tblCellMar')):
        tblpr.remove(v)
    tblpr.append(mar)
    filas = tabla.rows
    for i, fila in enumerate(filas):
        trpr = fila._tr.get_or_add_trPr()
        if trpr.find(qn('w:cantSplit')) is None:
            trpr.append(OxmlElement('w:cantSplit'))
        encabezado = i == 0 and not ficha
        if encabezado:
            th = OxmlElement('w:tblHeader'); trpr.append(th)
        for j, celda in enumerate(fila.cells):
            tcpr = celda._tc.get_or_add_tcPr()
            if encabezado:
                sombra(tcpr, VERDE2)
            elif ficha and j == 0:
                sombra(tcpr, CEBRA)
            elif not ficha and i % 2 == 0:
                sombra(tcpr, CEBRA)
            for p in celda.paragraphs:
                pf = p.paragraph_format
                pf.line_spacing = 1.15; pf.space_before = Pt(0); pf.space_after = Pt(0)
                pf.alignment = WD_ALIGN_PARAGRAPH.LEFT
                if len(filas) <= 14 and i < len(filas) - 1:
                    pf.keep_with_next = True
                for r in p.runs:
                    mono = r.style is not None and r.style.name == 'Verbatim Char'
                    if encabezado:
                        fuente(r, 8.5, mono=mono, rgb='FFFFFF', bold=True)
                    elif ficha and j == 0:
                        fuente(r, 8.5, mono=mono, rgb=GRIS)
                    else:
                        fuente(r, 8.5, mono=mono)


# ---------------------------------------------------------------- partes del documento

def parrafo_imagen(doc, ruta, ancho, alto):
    p = doc.add_paragraph()
    pf = p.paragraph_format
    pf.space_before = Pt(0); pf.space_after = Pt(0); pf.line_spacing = 1.0
    p.add_run().add_picture(ruta, width=ancho, height=alto)
    return p


def seccion_sin_margenes(sectpr):
    for el in list(sectpr):
        if el.tag in (qn('w:headerReference'), qn('w:footerReference'), qn('w:pgNumType'), qn('w:titlePg')):
            sectpr.remove(el)
    m = sectpr.find(qn('w:pgMar'))
    for a in ('top', 'bottom', 'left', 'right', 'header', 'footer', 'gutter'):
        m.set(qn('w:' + a), '0')


def encabezado_y_pie(sec, m):
    sec.header.is_linked_to_previous = False
    sec.footer.is_linked_to_previous = False
    ancho = sec.page_width - sec.left_margin - sec.right_margin
    cab = sec.header.paragraphs[0]
    cab.paragraph_format.tab_stops.add_tab_stop(ancho, WD_TAB_ALIGNMENT.RIGHT)
    texto(cab, f'{PRODUCTO} {VERSION}', 7.5, VERDE, True)
    texto(cab, '\t' + m['titulo'], 7.5, GRIS)
    borde_parrafo(cab._p, 'bottom', 'DBE4DD', sz=4, espacio='4')
    pie = sec.footer.paragraphs[0]
    pie.paragraph_format.tab_stops.add_tab_stop(ancho, WD_TAB_ALIGNMENT.RIGHT)
    texto(pie, f"{m['codigo']} · Edición 1.0 · {FECHA}\t", 7.5, GRIS)
    texto(pie, 'Página ', 7.5, VERDE2, True)
    campo(pie, 'PAGE', VERDE2, 7.5, True)


def titulo_fm(doc, t, sub):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    texto(p, t, 20, VERDE, True)
    p2 = doc.add_paragraph()
    p2.paragraph_format.space_after = Pt(12)
    texto(p2, sub, 9, GRIS)
    return [p, p2]


def rotulo(doc, t):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(12); p.paragraph_format.space_after = Pt(5)
    p.paragraph_format.keep_with_next = True
    texto(p, t.upper(), 7.5, ORO, True)
    return p


def tabla_simple(doc, filas, encabezado=True, ficha=False):
    t = doc.add_table(rows=len(filas), cols=len(filas[0]))
    for i, fila in enumerate(filas):
        for j, v in enumerate(fila):
            celda = t.cell(i, j)
            p = celda.paragraphs[0]
            if isinstance(v, tuple):
                txt, estilo = v
                r = p.add_run(txt)
                if estilo == 'i':
                    r.italic = True
                elif estilo == 'm':
                    r.font.name = MONO
            else:
                p.add_run(v)
    tabla_producto(t, ficha=ficha)
    return t


def ficha(doc, m):
    els = titulo_fm(doc, 'Información del documento', f"{PRODUCTO} {VERSION} · {m['titulo']}")
    els.append(tabla_simple(doc, [
        ['Producto', f'{PRODUCTO} {VERSION}, plataforma de gestión documental de pasantías'],
        ['Documento', m['titulo']], ['Código y edición', f"{m['codigo']}, edición 1.0"],
        ['Fecha de publicación', FECHA], ['Dirigido a', m['publico']], ['Cliente', CLIENTE]], ficha=True))
    els.append(rotulo(doc, 'Equipo de desarrollo'))
    els.append(tabla_simple(doc, [['Nombre', 'Participación']] +
                            [[n, 'Análisis, desarrollo, pruebas y documentación'] for n in EQUIPO]))
    els.append(rotulo(doc, 'Historial de ediciones'))
    els.append(tabla_simple(doc, [['Edición', 'Fecha', 'Versión del producto', 'Descripción'],
                                  ['1.0', FECHA_CORTA, VERSION,
                                   f'Primera edición del manual para el lanzamiento de la versión {VERSION}']]))
    els.append(rotulo(doc, 'Convenciones'))
    els.append(tabla_simple(doc, [['Formato', 'Uso'],
                                  [('Cursiva', 'i'), 'Nombres de botones, menús, campos y estados de la plataforma'],
                                  [('Monoespaciado', 'm'), 'Comandos, archivos, rutas y valores que se escriben tal como aparecen'],
                                  ['Tabla N / Figura N', 'Tablas y capturas de pantalla, numeradas en orden dentro del manual']]))
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(12)
    borde_parrafo(p._p, 'top', 'DBE4DD', sz=4, espacio='6')
    texto(p, f'La información de este manual corresponde a la versión {VERSION} de {PRODUCTO} y puede cambiar en '
             'ediciones posteriores. Las capturas de pantalla usan datos de ejemplo; los nombres, las empresas y las '
             f'fechas de una instalación real serán distintos. El logotipo de la {CLIENTE} identifica a la institución '
             'para la cual se desarrolló el producto.', 7.5, GRIS)
    p.add_run().add_break(WD_BREAK.PAGE)
    els.append(p)
    return els


def indice(doc, m):
    els = titulo_fm(doc, 'Contenido', f"{m['titulo']} de {PRODUCTO} {VERSION}")
    p = doc.add_paragraph()
    run = p.add_run()
    for tipo in ('begin', 'instr', 'separate'):
        if tipo == 'instr':
            el = OxmlElement('w:instrText'); el.set(qn('xml:space'), 'preserve'); el.text = ' TOC \\o "2-3" \\h \\z \\u '
        else:
            el = OxmlElement('w:fldChar'); el.set(qn('w:fldCharType'), tipo)
            if tipo == 'begin':
                el.set(qn('w:dirty'), 'true')
        run._r.append(el)
    texto(p, 'Haga clic derecho aquí y elija Actualizar campo para mostrar el índice.', 9, GRIS, italic=True)
    fin = p.add_run()
    el = OxmlElement('w:fldChar'); el.set(qn('w:fldCharType'), 'end'); fin._r.append(el)
    els.append(p)
    return els


def estilos_indice(doc):
    est = {s.name: s for s in doc.styles}
    for nivel, size, rgb, bold, sangria, antes in ((2, 10, VERDE, True, 0, 6), (3, 9, TINTA, False, 18, 0)):
        nombre = f'toc {nivel}'
        e = est.get(nombre) or est.get(f'TOC {nivel}')
        if e is None:
            e = doc.styles.add_style(f'TOC {nivel}', 1)
            e.base_style = est['Normal']
        fuente(e, size, rgb=rgb, bold=bold)
        pf = e.paragraph_format
        pf.left_indent = Pt(sangria); pf.space_before = Pt(antes); pf.space_after = Pt(3)


# ---------------------------------------------------------------- documento

def procesar(m, ref):
    raiz = os.getcwd()
    portadas = os.path.join(raiz, 'docs/word/portadas')
    tapa = os.path.join(portadas, m['codigo'] + '-portada.png')
    contra = os.path.join(portadas, m['codigo'] + '-contraportada.png')
    if not (os.path.exists(tapa) and os.path.exists(contra)):
        sys.exit('Faltan las imágenes de portada: ejecute primero node docs/pdf/generar_manuales.js')
    with tempfile.TemporaryDirectory() as tmp:
        entrada = preparar_md(m['md'], tmp)
        subprocess.run(['pandoc', entrada, '-f', 'gfm', '-t', 'docx', '--resource-path',
                        os.path.dirname(os.path.abspath(m['md'])), '--reference-doc', ref, '-o', m['docx']], check=True)
    doc = Document(m['docx'])
    ajustar_estilos(doc)
    estilos_indice(doc)

    # Sección principal: carta, márgenes, encabezado y pie, numeración desde 1.
    sec = doc.sections[0]
    sec.page_width, sec.page_height = Inches(8.5), Inches(11)
    sec.top_margin, sec.bottom_margin = Mm(24), Mm(22)
    sec.left_margin = sec.right_margin = Mm(20)
    sec.header_distance, sec.footer_distance = Mm(11), Mm(10)
    encabezado_y_pie(sec, m)
    pg = OxmlElement('w:pgNumType'); pg.set(qn('w:start'), '1'); sec._sectPr.append(pg)

    # Ajustes del cuerpo: imágenes, leyendas y tablas.
    ancho_util, alto_max = Inches(6.4), Inches(3.7)
    for p in doc.paragraphs:
        t = p.text.strip()
        if p._p.findall('.//' + qn('w:drawing')):
            pf = p.paragraph_format
            pf.alignment = WD_ALIGN_PARAGRAPH.CENTER; pf.space_after = Pt(12); pf.line_spacing = 1.0
            for ext in p._p.iter(qn('wp:extent')):
                cx, cy = int(ext.get('cx')), int(ext.get('cy'))
                k = min(1.0, ancho_util / cx, alto_max / cy)
                ext.set('cx', str(int(cx * k))); ext.set('cy', str(int(cy * k)))
                for a in p._p.iter(qn('a:ext')):
                    a.set('cx', str(int(cx * k))); a.set('cy', str(int(cy * k)))
        elif re.match(r'^(Tabla|Figura) \d+\. ', t):
            pf = p.paragraph_format
            pf.space_before = Pt(10); pf.space_after = Pt(4); pf.keep_with_next = True
            for i, r in enumerate(p.runs):
                fuente(r, 8.5, rgb=VERDE2 if i == 0 else GRIS, bold=(i == 0))
    for t in doc.tables:
        tabla_producto(t)
        sig = t._tbl.getnext()
        while sig is not None and sig.tag in (qn('w:bookmarkStart'), qn('w:bookmarkEnd')):
            sig = sig.getnext()
        if sig is not None and sig.tag == qn('w:p'):
            ppr = sig.get_or_add_pPr()
            esp = ppr.find(qn('w:spacing'))
            if esp is None:
                esp = OxmlElement('w:spacing'); ppr.append(esp)
            esp.set(qn('w:before'), '200')
    # El primer capítulo empieza en la página siguiente al índice, sin salto adicional.
    primero = next(p for p in doc.paragraphs if p.style.name == 'Heading 2')

    # Contraportada: sección final sin márgenes ni encabezado.
    final = doc.add_section(WD_SECTION.NEW_PAGE)
    seccion_sin_margenes(final._sectPr)
    final.header.is_linked_to_previous = False
    final.footer.is_linked_to_previous = False
    parrafo_imagen(doc, contra, Inches(8.5), Inches(10.98))

    # Portada, ficha e índice antes del primer capítulo.
    cuerpo = doc.element.body
    inicio = cuerpo[0]
    p_tapa = parrafo_imagen(doc, tapa, Inches(8.5), Inches(10.98))
    p_corte = doc.add_paragraph()
    sp = copy.deepcopy(sec._sectPr)
    seccion_sin_margenes(sp)
    p_corte._p.get_or_add_pPr().append(sp)
    for el in [p_tapa._p, p_corte._p] + [e._p if hasattr(e, '_p') else e._tbl for e in ficha(doc, m) + indice(doc, m)]:
        inicio.addprevious(el)
    primero.paragraph_format.page_break_before = True

    # Word actualiza el índice y los campos al abrir el archivo.
    ajustes = doc.settings.element
    uf = OxmlElement('w:updateFields'); uf.set(qn('w:val'), 'true')
    ancla = None
    for nombre in ('hdrShapeDefaults', 'footnotePr', 'endnotePr', 'compat', 'docVars', 'rsids'):
        ancla = ajustes.find(qn('w:' + nombre))
        if ancla is not None:
            break
    if ancla is not None:
        ancla.addprevious(uf)
    else:
        ajustes.append(uf)
    # Identificadores de listas con la longitud que exige el formato (8 dígitos hexadecimales).
    numeracion = doc.part.numbering_part.element
    for nsid in numeracion.iter(qn('w:nsid')):
        nsid.set(qn('w:val'), nsid.get(qn('w:val')).zfill(8)[-8:])
    ordenar_xml(doc)
    doc.core_properties.title = f"{m['titulo']} de {PRODUCTO} {VERSION}"
    doc.core_properties.author = 'Equipo de desarrollo ' + PRODUCTO
    doc.core_properties.subject = m['codigo']
    doc.save(m['docx'])
    print('Word generado:', m['docx'])


ORDEN_SETTINGS = ['writeProtection', 'view', 'zoom', 'removePersonalInformation', 'removeDateAndTime',
                  'doNotDisplayPageBoundaries', 'displayBackgroundShape', 'printPostScriptOverText',
                  'printFractionalCharacterWidth', 'printFormsData', 'embedTrueTypeFonts', 'embedSystemFonts',
                  'saveSubsetFonts', 'saveFormsData', 'mirrorMargins', 'alignBordersAndEdges',
                  'bordersDoNotSurroundHeader', 'bordersDoNotSurroundFooter', 'gutterAtTop', 'hideSpellingErrors',
                  'hideGrammaticalErrors', 'activeWritingStyle', 'proofState', 'formsDesign', 'attachedTemplate',
                  'linkStyles', 'stylePaneFormatFilter', 'stylePaneSortMethod', 'documentType', 'mailMerge',
                  'revisionView', 'trackRevisions', 'doNotTrackMoves', 'doNotTrackFormatting', 'documentProtection',
                  'autoFormatOverride', 'styleLockTheme', 'styleLockQFSet', 'defaultTabStop', 'autoHyphenation',
                  'consecutiveHyphenLimit', 'hyphenationZone', 'doNotHyphenateCaps', 'showEnvelope',
                  'summaryLength', 'clickAndTypeStyle', 'defaultTableStyle', 'evenAndOddHeaders',
                  'bookFoldRevPrinting', 'bookFoldPrinting', 'bookFoldPrintingSheets',
                  'drawingGridHorizontalSpacing', 'drawingGridVerticalSpacing', 'displayHorizontalDrawingGridEvery',
                  'displayVerticalDrawingGridEvery', 'doNotUseMarginsForDrawingGridOrigin',
                  'drawingGridHorizontalOrigin', 'drawingGridVerticalOrigin', 'doNotShadeFormData',
                  'noPunctuationKerning', 'characterSpacingControl', 'printTwoOnOne', 'strictFirstAndLastChars',
                  'noLineBreaksAfter', 'noLineBreaksBefore', 'savePreviewPicture', 'doNotValidateAgainstSchema',
                  'saveInvalidXml', 'ignoreMixedContent', 'alwaysShowPlaceholderText', 'doNotDemarcateInvalidXml',
                  'saveXmlDataOnly', 'useXSLTWhenSaving', 'saveThroughXslt', 'showXMLTags',
                  'alwaysMergeEmptyNamespace', 'updateFields', 'hdrShapeDefaults', 'footnotePr', 'endnotePr',
                  'compat', 'docVars', 'rsids', 'mathPr', 'attachedSchema', 'themeFontLang', 'clrSchemeMapping',
                  'doNotIncludeSubdocsInStats', 'doNotAutoCompressPictures', 'forceUpgrade', 'captions',
                  'readModeInkLockDown', 'smartTagType', 'schemaLibrary', 'shapeDefaults',
                  'doNotEmbedSmartTags', 'decimalSymbol', 'listSeparator']


def reordenar_settings(el):
    reordenar(el, ORDEN_SETTINGS)


def main():
    os.chdir(os.path.abspath(os.path.join(AQUI, '..', '..')))
    os.makedirs('docs/word', exist_ok=True)
    with tempfile.TemporaryDirectory() as tmp:
        ref = os.path.join(tmp, 'ref.docx')
        with open(ref, 'wb') as f:
            f.write(subprocess.run(['pandoc', '--print-default-data-file', 'reference.docx'],
                                   check=True, capture_output=True).stdout)
        for m in MANUALES:
            procesar(m, ref)


if __name__ == '__main__':
    sys.exit(main())
