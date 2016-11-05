using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace CsharpHttpHelper.Helper
{
    internal class EncodingHelper
    {
        /// <summary>
        /// 将字节数组转为字符串
        /// </summary>
        /// <param name="b">字节数组</param>
        /// <param name="e">编码，默认为Default</param>
        /// <returns></returns>
        internal static string ByteToString(byte[] b, Encoding e = null)
        {
            if (e == null)
            {
                e = Encoding.Default;
            }
            string result = e.GetString(b);
            return result;
        }

        /// <summary>
        /// 将字符串转为字节数组
        /// </summary>
        /// <param name="s">字符串</param>
        /// <param name="e">编码，默认为Default</param>
        /// <returns></returns>
        internal static byte[] StringToByte(string s, Encoding e = null)
        {
            if (e == null)
            {
                e = Encoding.Default;
            }
            byte[] b = e.GetBytes(s);
            return b;
        }

        internal static String UnicodeToZn(String s)
        {
            string o = "";
            s = s.Replace(" ", "").Replace("\r", "").Replace("\n", "").Replace("\r\n", "").Replace("\t", "");
            if (s.Length % 4 != 0)
            {
            }
            else
            {
                int len = s.Length / 2;
                byte[] b = new byte[len];
                for (int i = 0; i < s.Length; i += 2)
                {
                    string bi = s.Substring(i, 2);
                    b[i / 2] = (byte)Convert.ToInt32(bi, 16);
                }
                o = Encoding.Unicode.GetString(b);
            }
            return o;
        }
    }
}
