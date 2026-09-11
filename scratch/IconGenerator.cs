using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.IO;

public class IconGenerator {
    public static Bitmap CreateIcon(int size) {
        Bitmap bmp = new Bitmap(size, size, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(bmp)) {
            g.SmoothingMode = SmoothingMode.AntiAlias;
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.PixelOffsetMode = PixelOffsetMode.HighQuality;
            g.Clear(Color.Transparent);

            float scale = size / 64.0f;
            float pad = 2f * scale;
            float w = 60f * scale;
            float h = 60f * scale;
            float r = 18f * scale;

            // Rounded rectangle background
            using (GraphicsPath path = new GraphicsPath()) {
                path.AddArc(pad, pad, r * 2, r * 2, 180, 90);
                path.AddArc(pad + w - r * 2, pad, r * 2, r * 2, 270, 90);
                path.AddArc(pad + w - r * 2, pad + h - r * 2, r * 2, r * 2, 0, 90);
                path.AddArc(pad, pad + h - r * 2, r * 2, r * 2, 90, 90);
                path.CloseFigure();

                using (LinearGradientBrush bg = new LinearGradientBrush(
                    new PointF(0, size), new PointF(size, 0),
                    Color.FromArgb(255, 9, 13, 22),
                    Color.FromArgb(255, 79, 70, 229))) {
                    g.FillPath(bg, path);
                }

                using (Pen border = new Pen(Color.FromArgb(70, 129, 140, 248), 1.5f * scale)) {
                    g.DrawPath(border, path);
                }
            }

            // Draw Infinity Loop
            using (GraphicsPath inf = new GraphicsPath()) {
                inf.AddBezier(new PointF(21f * scale, 23f * scale), new PointF(14f * scale, 23f * scale), new PointF(10f * scale, 27f * scale), new PointF(10f * scale, 32f * scale));
                inf.AddBezier(new PointF(10f * scale, 32f * scale), new PointF(10f * scale, 37f * scale), new PointF(14f * scale, 41f * scale), new PointF(21f * scale, 41f * scale));
                inf.AddBezier(new PointF(21f * scale, 41f * scale), new PointF(27.5f * scale, 41f * scale), new PointF(30.5f * scale, 37f * scale), new PointF(32f * scale, 34.5f * scale));
                inf.AddBezier(new PointF(32f * scale, 34.5f * scale), new PointF(33.5f * scale, 37f * scale), new PointF(36.5f * scale, 41f * scale), new PointF(43f * scale, 41f * scale));
                inf.AddBezier(new PointF(43f * scale, 41f * scale), new PointF(50f * scale, 41f * scale), new PointF(54f * scale, 37f * scale), new PointF(54f * scale, 32f * scale));
                inf.AddBezier(new PointF(54f * scale, 32f * scale), new PointF(54f * scale, 27f * scale), new PointF(50f * scale, 23f * scale), new PointF(43f * scale, 23f * scale));
                inf.AddBezier(new PointF(43f * scale, 23f * scale), new PointF(36.5f * scale, 23f * scale), new PointF(33.5f * scale, 27f * scale), new PointF(32f * scale, 29.5f * scale));
                inf.AddBezier(new PointF(32f * scale, 29.5f * scale), new PointF(30.5f * scale, 27f * scale), new PointF(27.5f * scale, 23f * scale), new PointF(21f * scale, 23f * scale));

                using (LinearGradientBrush infBrush = new LinearGradientBrush(
                    new PointF(10f * scale, 20f * scale), new PointF(54f * scale, 44f * scale),
                    Color.FromArgb(255, 56, 189, 248),
                    Color.FromArgb(255, 52, 211, 153))) {
                    using (Pen infPen = new Pen(infBrush, Math.Max(2f, 6.5f * scale))) {
                        infPen.StartCap = LineCap.Round;
                        infPen.EndCap = LineCap.Round;
                        infPen.LineJoin = LineJoin.Round;
                        g.DrawPath(infPen, inf);
                    }
                }
            }

            // Upward Growth Spark
            float sx = 43f * scale;
            float sy = 23f * scale;
            float ro = Math.Max(2f, 3.5f * scale);
            float ri = Math.Max(1f, 1.5f * scale);
            using (SolidBrush so = new SolidBrush(Color.FromArgb(255, 52, 211, 153))) {
                g.FillEllipse(so, sx - ro, sy - ro, ro * 2, ro * 2);
            }
            using (SolidBrush si = new SolidBrush(Color.White)) {
                g.FillEllipse(si, sx - ri, sy - ri, ri * 2, ri * 2);
            }
        }
        return bmp;
    }

    public static void SaveIco(string outputPath, Bitmap[] bitmaps) {
        using (FileStream fs = new FileStream(outputPath, FileMode.Create))
        using (BinaryWriter bw = new BinaryWriter(fs)) {
            bw.Write((ushort)0); // reserved
            bw.Write((ushort)1); // icon type
            bw.Write((ushort)bitmaps.Length);

            byte[][] pngData = new byte[bitmaps.Length][];
            for (int i = 0; i < bitmaps.Length; i++) {
                using (MemoryStream ms = new MemoryStream()) {
                    bitmaps[i].Save(ms, ImageFormat.Png);
                    pngData[i] = ms.ToArray();
                }
            }

            int offset = 6 + (16 * bitmaps.Length);
            for (int i = 0; i < bitmaps.Length; i++) {
                int s = bitmaps[i].Width >= 256 ? 0 : bitmaps[i].Width;
                bw.Write((byte)s); // width
                bw.Write((byte)s); // height
                bw.Write((byte)0); // colors
                bw.Write((byte)0); // reserved
                bw.Write((ushort)1); // planes
                bw.Write((ushort)32); // bit count
                bw.Write((uint)pngData[i].Length); // bytes in res
                bw.Write((uint)offset); // offset
                offset += pngData[i].Length;
            }

            for (int i = 0; i < bitmaps.Length; i++) {
                bw.Write(pngData[i]);
            }
        }
    }
}

