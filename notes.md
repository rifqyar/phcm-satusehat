Akun satu sehat yg sudah terverifikasi 
Url : https://satusehat.kemkes.go.id/platform/login
email : adian@ilcs.co.id
Password : Ilcs2022!

USER SATU SEHAT
DOKTER
nipp:61297117
password:Pelindo1!

SA
username: 59805019
password: Mantap123!


00181969

################################################################################################################################
notes
ID UNIT RS PHC MEDAN = 001


################################################################################################################################
list obat per pasien
https://sim.phcm.co.id/ermjalan-medan/index.php/rawatjalan/getlistriwayatobat/0052752835caa6ce3805825f946bd263619c7d03c0db40307502bd69b31a251b6efc079e054c4310cbd8199e5b8c9fa84bbc29609dfc7bcda18aec7d7b0e683fOzCTSKXuHc2YjTysoAS75zI5Fjc6YHnZLtzejlkqH6g~

http://10.1.19.22/ermjalan-medan/index.php/rawatjalan/getlistriwayatobat/b65e109d47f76fdddd2de57fd7eea44083bd5cbe6e610bd84bcfd52e52df8aa111e25c191b7c8d67355240fb9da4109ffce4ba1d68cade5f46078428d796b6bbh3n-uv5XwPItDWyBHly8wibT_kqslB15Lybzob-j9XE~
cek riwayat obat
kbuku=00036717&dokter=392d0a290d85f201a58d54aa2371b869af2631df41ca3ec1b4582013091a297cb911b3a2e6340aa216db3d37d341c288e72e375dc6770fc02199c117c75a80dbAOLyloFT9Oc72GrrBgiX5a8l9xUCiceawv6ane6SHgo~&klinik=394d4439b9fec530c3e2610a45782cd1dbf613eb7d35b5ebb93d2400c5e5f6fe4f7ac612466474437eab8ef1fddbadd17187e2e731f2b9124535fb2afae5bb55NCqtiaYD6K4A9X0KSBf5ZgYSNvMKWARI9_YmHa2gTZA~
detail obat
http://10.1.19.22/ermjalan-medan/index.php/rawatjalan/lihatriwayatobat/d9f32cef0576cec53ed51d2bb48acd89377ff43891e6b28a4dfe04a9f768dffaadace90fd9afb5a5336d3dc65ba0aaff7a4baf0e3bc1983c8df7e4aad0325982Iwq94wToKhzo9TkdZRuld1M6owgnkBPzlT5DnImBb3s~/fe8b1e468627984a6d36ade3bfe8a041e17ccd4ea8c086f3e3fd68932cc0cd1c6aa283b06bd795c4de7ff38b9b0211f4671fc4698ee4ccf1dd39d778c1230a5euZ6IUgzMdtZ0858EZ0SpSshUgezG2PBj9bg8bpKkVaU~/3981a81f5a9e7849ef5c8764cf01500f313b7141270b4eacf9e5db6255cb360dff24d9f6299e548d6cf0dd01d1231c83b7e657dbbda2d176b0f9e349c01af772dqcW52eEYouuIp1Akh-GRHWMRD3V9JV2E4TtE0-iwLI~


karcis rawat inap dengan obat multi date
93843


string(2601) "string(2585) "
    DECLARE @dokter varchar(50) = '019';
    DECLARE @klinik varchar(50) = '0014';
    DECLARE @tanggalAwal date = '2025-11-03';
    DECLARE @tanggalAkhir date = '2025-11-03';
    DECLARE @unit varchar(50) = '001';
    SET NOCOUNT ON;
    BEGIN

    SELECT DISTINCT
        a.KLINIK,
        a.NOURUT AS A,
        CONCAT(a.KARCIS,' (',CONVERT(varchar(10),a.TGL,105),')') AS B,
        --a.NO_PESERTA AS C,
        a.KBUKU AS C,
				a.NO_PESERTA AS CCC,
        b.NAMA AS D,
        c.NMDEBT AS E,
        b.TGL_LHR AS F,
        'N'
         AS G,
        a.KARCIS AS H,
        a.TGL AS I,
        CASE WHEN k.NO_KUNJUNG IS NULL
          THEN 'BELUM'
          ELSE 'SELESAI'
        END AS J,
        CASE WHEN m.NO_KUNJUNG IS NULL
          THEN 'TUTUP'
          ELSE 'BUKA'
        END AS K
      FROM
      (
        SELECT *
        FROM SIRS_PHCM.dbo.RJ_KARCIS
        WHERE
          ISNULL(SELESAI,0) NOT IN ('9','10') AND
        --  ISNULL(NOREG,'') = '' AND
          KLINIK = @klinik AND
          IDUNIT = @unit AND
          KDDOK =
          CASE WHEN @klinik = '102.1'
            THEN @dokter
            ELSE @dokter
          END AND
          (CONVERT(DATE,TGL) BETWEEN @tanggalAwal AND @tanggalAkhir) AND
          ISNULL(STBTL,0) = 0
      )  a
      JOIN SIRS_PHCM.dbo.RIRJ_MASTERPX b
      ON (a.NO_PESERTA=b.NO_PESERTA)
      JOIN SIRS_PHCM.dbo.RIRJ_MDEBITUR c
      ON (a.KDEBT=c.KDDEBT)

      LEFT JOIN
      (
        SELECT *
        FROM E_RM_PHCM.dbo.ERM_NOMOR_KUNJUNG
        WHERE
          KDDOK =
          CASE WHEN @klinik = '102.1'
            THEN @dokter
            ELSE @dokter
          END AND
          KDKLIN = @klinik AND
          (CONVERT(DATE,TGL) BETWEEN @tanggalAwal AND CONVERT(DATE,dbo.convert_timezone(GETDATE()))) AND
          AKTIF =
            CASE WHEN @klinik = '102.1'
              THEN '1'
              ELSE AKTIF
            END
      ) j
      ON
       a.KARCIS = j.KARCIS AND
       a.IDUNIT = j.IDUNIT
      LEFT JOIN (SELECT * FROM E_RM_PHCM.dbo.ERM_RM_IRJA WHERE AKTIF = '1') k
      ON j.NO_KUNJUNG = k.NO_KUNJUNG
      LEFT JOIN
      (
        SELECT * FROM
        E_RM_PHCM.dbo.ERM_PERMINTAAN_ISIAN
        WHERE
          AKTIF = 'true'
      ) m
      ON j.NO_KUNJUNG = m.NO_KUNJUNG
      WHERE
        (CONVERT(DATE,a.TGL) BETWEEN @tanggalAwal AND @tanggalAkhir) AND
        ISNULL(a.STBTL,0)=0 AND
        a.KDDOK =
        CASE WHEN @klinik = '102.1'
            THEN @dokter
            ELSE @dokter
          END AND
        a.KLINIK = @klinik
      ORDER BY a.KLINIK,a.NOURUT ASC

    END
    "
"


string(3369) "string(3353) "DECLARE @nota bigint = '8258'; DECLARE @idUnit varchar(50) = '001'; IF @nota = 0 GOTO ERRORX SET NOCOUNT ON; BEGIN DECLARE @CURTEMP CURSOR DECLARE @TEMPNOMOR INT=0 DECLARE @NO INT DECLARE @ID INT DECLARE @NMBRG VARCHAR(80) DECLARE @SIGNA VARCHAR(20) DECLARE @KET VARCHAR(100) DECLARE @JUMLAH INT DECLARE @TEMP TABLE(IDTRANS varchar(200), INFO VARCHAR(10),[NO] INT,NAMA_OBAT VARCHAR(80),SIGNA VARCHAR(20),KET VARCHAR(100),JUMLAH INT, TGL_ENTRY datetime) DECLARE @karcis int=0 SET @karcis = ( SELECT TOP 1 KARCIS FROM SIRS_PHCM..RJ_KARCIS_BAYAR WHERE NOTA=@nota AND ISNULL(STBTL,0)=0 AND IDUNIT = @idUnit ORDER BY KARCIS DESC ) INSERT INTO @TEMP(IDTRANS, TGL_ENTRY, INFO,[NO],NAMA_OBAT,SIGNA,KET,JUMLAH) SELECT T.ID_TRANS, H.TGL, 'R/' INFO,T.[NO],T.NAMABRG NAMA_OBAT,T.SIGNA2 SIGNA,T.KETQTY KET,SUM(T.JUMLAH) JUMLAH FROM (SELECT * FROM SIRS_PHCM..IF_HTRANS WHERE ISNUMERIC(NOTA) = 1 AND IDUNIT = @idUnit ) H INNER JOIN (SELECT * FROM SIRS_PHCM..IF_TRANS) T ON H.ID_TRANS=T.ID_TRANS WHERE ISNUMERIC(NOTA) = 1 AND CONVERT(bigint,NOTA)=CONVERT(bigint,@nota) AND H.ACTIVE=1 AND H.IDUNIT = @idUnit AND T.ID = 0 AND ISNULL(ID_tRANS_KF,'')='' GROUP BY T.[NO], T.NAMABRG, T.SIGNA2, T.KETQTY, T.ID_TRANS, H.TGL SET @CURTEMP = CURSOR LOCAL FAST_FORWARD FOR SELECT T.[NO] [NO],T.[ID],T.NAMABRG NAMA_OBAT,T.SIGNA2 SIGNA,T.KETQTY KET,T.JUMLAH JUMLAH FROM SIRS_PHCM..IF_HTRANS H INNER JOIN SIRS_PHCM..IF_TRANS T ON H.ID_TRANS=T.ID_TRANS WHERE H.IDUNIT = @idUnit AND CONVERT(bigint,NOTA)=CONVERT(bigint,@nota) AND H.ACTIVE=1 AND T.ID <> 0 AND ISNULL(ID_tRANS_KF,'')='' ORDER BY 1,2 OPEN @CURTEMP FETCH NEXT FROM @CURTEMP INTO @NO,@ID,@NMBRG,@SIGNA,@KET,@JUMLAH WHILE @@FETCH_STATUS = 0 BEGIN IF @TEMPNOMOR=0 OR @TEMPNOMOR<>@NO BEGIN INSERT INTO @TEMP(INFO,[NO],NAMA_OBAT,SIGNA,KET,JUMLAH) VALUES('R/',@NO,@NMBRG,@SIGNA,@KET,@JUMLAH) END ELSE BEGIN INSERT INTO @TEMP(INFO,[NO],NAMA_OBAT,SIGNA,KET,JUMLAH) VALUES('',@NO,@NMBRG,@SIGNA,@KET,@JUMLAH) END SET @TEMPNOMOR=@NO FETCH NEXT FROM @CURTEMP INTO @NO,@ID,@NMBRG,@SIGNA,@KET,@JUMLAH END CLOSE @CURTEMP DEALLOCATE @CURTEMP END SET TRANSACTION ISOLATION LEVEL READ COMMITTED; GOTO SELESAI ERRORX: SELECT '' INFO,'' NAMA_OBAT,'' SIGNA,'' KET,0 JUMLAH, '' AS IDTRANS, NULL AS TGL_ENTRY; SELESAI: SELECT DISTINCT [NO], INFO,NAMA_OBAT,SIGNA,KET,JUMLAH, TGL_ENTRY, IDTRANS FROM @TEMP WHERE IDTRANS IS NOT NULL ORDER BY IDTRANS, [NO]; " " 