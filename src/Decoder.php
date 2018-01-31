<?php namespace dehydr8\FMR;

class Decoder {

  private $data;
  private $idx;
  private $debug;
  
  public function __construct($data, $debug = false) {
    $this->data = $data;
    $this->idx = 0;
    $this->debug = $debug;
  }
  
  private function unpack($format, $increment = 0) {
    $ret = unpack($format, substr($this->data, $this->idx));
    $this->idx += $increment;
    return $ret[1];
  }
  
  private function readBytes($length) {
    $value = substr($this->data, $this->idx, $length);
    $this->idx += $length;
    return $value;
  }
  
  private function readByte() {
    return $this->unpack("C", 1);
  }

  private function readShort() {
    return
      ($this->readByte() << 8) +
      ($this->readByte() << 0);
  }
  
  private function readUnsignedInt() {
    return $this->unpack("N", 4);
  }

  private function readInt() {
    $unsigned = $this->readUnsignedInt();
    return unpack("l", pack("l", $unsigned))[1];
  }

  private function log($message) {
    if ($this->debug)
      echo "[-] $message\r\n";
  }

  public function decode() {
    $data = array();

    $header = trim($this->readBytes(4));
    $this->log("Header: $header");

    if ($header !== "FMR")
      throw new \Exception("Header mismatch, expected FMR, got $header");

    $data["header"] = $header;

    $version = trim($this->readBytes(4));
    $this->log("Version: $version");

    $data["version"] = $version;

    $numberOfRecords = $this->readUnsignedInt();
    $this->log("numberOfRecords: $numberOfRecords");

    $data["num_records"] = $numberOfRecords;

    $captureEquipmentDetails = $this->readBytes(2);
    
    $imageSizeX = $this->readShort();
    $imageSizeY = $this->readShort();

    $this->log("X = $imageSizeX, Y = $imageSizeY");

    $data["size"] = array(
      "x" => $imageSizeX,
      "y" => $imageSizeY,
    );

    $resolutionX = $this->readShort();
    $resolutionY = $this->readShort();

    $this->log("res X = $resolutionX, res Y = $resolutionY");

    $data["resolution"] = array(
      "x" => $resolutionX,
      "y" => $resolutionY,
    );

    $numberOfViews = $this->readByte();

    $this->log("numberOfViews = $numberOfViews");

    $data["num_views"] = $numberOfViews;

    // ignore the reserved byte
    $this->readByte();

    for ($i=0; $i<$numberOfViews; $i++) {
      $view = array();

      $fingerPosition = $this->readByte();
      $viewNumberAndImpressionType = $this->readByte();

      $viewNumber = ($viewNumberAndImpressionType >> 4) & 0xFF;
      $impressionType = $viewNumberAndImpressionType & 0x0F;
      $fingerQuality = $this->readByte();
      $numberOfMinutae = $this->readByte();

      $this->log("\tfingerPosition = $fingerPosition");
      $this->log("\tviewNumber = $viewNumber");
      $this->log("\timpressionType = $impressionType");
      $this->log("\tfingerQuality = $fingerQuality");
      $this->log("\tnumberOfMinutae = $numberOfMinutae");

      $view["position"] = $fingerPosition;
      $view["number"] = $viewNumber;
      $view["impression_type"] = $impressionType;
      $view["quality"] = $fingerQuality;
      $view["num_minutae"] = $numberOfMinutae;

      for ($j=0; $j<$numberOfMinutae; $j++) {
        $this->log("\t\t[-- $j --]\n");
        
        $mX = $this->readShort();
        $mType = ($mX >> 14) & 0x03;

        $mX = $mX & 0x3FFF;
        $mY = $this->readShort() & 0x3FFF;
        $mTheta = $this->readByte();
        $mQuality = $this->readByte();


        $this->log("\t\tmType = $mType");
        $this->log("\t\tmX = $mX");
        $this->log("\t\tmY = $mY");
        $this->log("\t\tmTheta = $mTheta");
        $this->log("\t\tmQuality = $mQuality");

        $view["minutae"][] = array(
          "t" => $mType,
          "x" => $mX,
          "y" => $mY,
          "r" => $mTheta,
          "q" => $mQuality,
        );
      }

      $extendedDataBlockLength = $this->readShort();
      $this->log("\textendedDataBlockLength = $extendedDataBlockLength");

      $view["extended_datablock_length"] = $extendedDataBlockLength;

      $data["views"][] = $view;
    }

    return $data;
  }
}