<?php

$filename = "\address_100.txt";
$file = @fopen($filename, 'r');
if ($file) {
   $data = explode("\r\n", fread($file, filesize($filename)));
}


$G = new Graph();
foreach($data as $address){
sleep(5);
$url = "https://blockchain.info/rawaddr/".$address."?format=json&n=5";
$json = file_get_contents($url);
$txs = json_decode($json,1)['txs'];

$G->addVertex($address);

//for loop instead of foreach and only do 5 transactions or all if less than 5
foreach($txs as $txinfo){

    $spendingTx = false ;
    // $totalSpent = 0 ;
    // $totalReceived = 0;

    // echo"<p>Txid = $txinfo[hash]<br>";
    //print_r($txinfo);

    // we need to find out if the address is the sender or the receiver
    $senderData = reset($txinfo['inputs']); //using reset to get only the first input
    if (isset($senderData['prev_out']['addr']) && $senderData['prev_out']['addr'] == $address ){
        //the address is the sender meaning the address is spending
        $spendingTx = true ;

    }

   //it's a spend tx then we cycle trough receivers
    if ($spendingTx) {

        foreach ($txinfo['out'] as $outgoingTransaction) {
          $receivingAddress = null;
            //Will display the receiving address
            if(isset($outgoingTransaction['addr'])) {
              $receivingAddress = $outgoingTransaction['addr'];
              // echo "<br>$address sent to $receivingAddress<br>";
              if($receivingAddress) {
                $G->addVertex($receivingAddress);
                $G->addEdge($address,$receivingAddress);
              }
            }

        }

    }
        //it is not a spending tx so it's a receceiving tx
        else {

            foreach ($txinfo['out'] as $outgoingTransaction) {
              $senderAddress = null;
                //We keep only receiving data concerning current wallet
                if (isset($outgoingTransaction['addr']) && $outgoingTransaction['addr'] == $address) {
                    //Will display the receiving address
                    $receivingAddress = $outgoingTransaction['addr'];
                    //will get the amount sent in satoshis
                    // $receivingAmountInSatoshi = $outgoingTransaction['value'];
                    if(isset($senderData['prev_out']['addr']))
                      $senderAddress = $senderData['prev_out']['addr'];
                    // $totalReceived = $receivingAmountInSatoshi;
                    // echo "<br>$address received from $senderAddress<br>";
                    if($senderAddress)
                    {
                      $G->addVertex($senderAddress);
                      $G->addEdge($senderAddress,$address);
                    }

                }

            }

        }
}
}

//====================================================================================

/**
 * Undirected graph implementation.
 */
class Graph
{
  /**
   * Adds an undirected edge between $u and $v in the graph.
   *
   * $u,$v can be anything.
   *
   * Edge (u,v) and (v,u) are the same.
   *
   * $data is the data to be associated with this edge.
   * If the edge (u,v) already exists, nothing will happen (the
   * new data will not be assigned).
   */

   private $vertex_data = array();
   private $adjacency_list = array();
   private $edge_count = 0;


  public function addEdge($u,$v,$data=null)
  {
    assert($this->sanityCheck());

    if ($this->hasEdge($u,$v))
      return;

    //If either u or v doesn't exist, create them.
    if (!$this->hasVertex($u))
      $this->addVertex($u);
    if (!$this->hasVertex($v))
      $this->addVertex($v);

    //Some sanity.
    assert(array_key_exists($u,$this->adjacency_list));
    assert(array_key_exists($v,$this->adjacency_list));

    //Associate (u,v) with data.
    $this->adjacency_list[$u][$v] = $data;
    //Associate (v,u) with data.
    $this->adjacency_list[$v][$u] = $data;

    //We just added two edges
    $this->edge_count += 2;

    assert($this->hasEdge($u,$v));

    assert($this->sanityCheck());
  }

  public function hasEdge($u,$v)
  {
    assert($this->sanityCheck());

    //If u or v do not exist, they surely do not make up an edge.
    if (!$this->hasVertex($u))
      return false;
    if (!$this->hasVertex($v))
      return false;


    //some extra sanity.
    assert(array_key_exists($u,$this->adjacency_list));
    assert(array_key_exists($v,$this->adjacency_list));

    //This is the return value; if v is a neighbor of u, then its true.
    $result = array_key_exists($v,$this->adjacency_list[$u]);

    //Make sure that iff v is a neighbor of u, then u is a neighbor of v
    assert($result == array_key_exists($u,$this->adjacency_list[$v]));

    return $result;
  }

  /**
   * Remove (u,v) and return data.
   */
  public function removeEdge($u,$v)
  {
    assert($this->sanityCheck());

    if (!$this->hasEdge($u,$v))
      return null;

    assert(array_key_exists($u,$this->adjacency_list));
    assert(array_key_exists($v,$this->adjacency_list));
    assert(array_key_exists($v,$this->adjacency_list[$u]));
    assert(array_key_exists($u,$this->adjacency_list[$v]));

    //remember data.
    $data = $this->adjacency_list[$u][$v];

    unset($this->adjacency_list[$u][$v]);
    unset($this->adjacency_list[$v][$u]);

    //We just removed two edges.
    $this->edge_count -= 2;

    assert($this->sanityCheck());

    return $data;
  }

  //Return data associated with (u,v)
  public function getEdgeData($u,$v)
  {
    assert($this->sanityCheck());

    //If no such edge, no data.
    if (!$hasEdge($u,$v))
      return null;

    //some sanity.
    assert(array_key_exists($u,$this->adjacency_list));
    assert(array_key_exists($v,$this->adjacency_list[$u]));


    return $this->adjacency_list[$u][$v];
  }

  /**
   * Add a vertex. Vertex must not exist, assertion failure otherwise.
   */
  public function addVertex($u,$data=null)
  {
    if(!$this->hasVertex($u))
    {
      //Associate data.
      $this->vertex_data[$u] = $data;
      //Create empty neighbor array.
      $this->adjacency_list[$u] = array();

      assert($this->hasVertex($u));
      assert($this->sanityCheck());
    }
  }

  public function hasVertex($u)
  {
    assert($this->sanityCheck());
    assert(array_key_exists($u,$this->vertex_data) == array_key_exists($u,$this->adjacency_list));
    return array_key_exists($u,$this->vertex_data);
  }

  //Returns data associated with vertex, null if vertex does not exist.
  public function getVertexData($u)
  {
    assert($this->sanityCheck());

    if (!array_key_exists($u,$this->vertex_data))
      return null;

    return $this->vertex_data[$u];
  }

  //Count the neighbors of a vertex.
  public function countVertexEdges($u)
  {
    assert($this->sanityCheck());

    if (!$this->hasVertex($u))
      return 0;

    //some sanity.
    assert (array_key_exists($u,$this->adjacency_list));

    return count($this->adjacency_list[$u]);
  }

  /**
   * Return an array of neighbor vertices of u.
   * If $with_data == true, then it will return an associative array, like so:
   * {neighbor => data}.
   */
  public function getEdgeVertices($u,$hasData=false)
  {
    assert($this->sanityCheck());

    if (!array_key_exists($u,$this->adjacency_list))
      return array();

    $result = array();

    if ($hasData) {
      foreach( $this->adjacency_list[$u] as $v=>$data)
      {
        $result[$v] = $data;
      }
    } else {

      foreach( $this->adjacency_list[$u] as $v=>$data)
      {
        array_push($result, $v);
      }
    }

    return $result;
  }

  public function sanityCheck()
  {
    if (count($this->vertex_data) != count($this->adjacency_list))
    {
      echo "vertex_data length != adjacency_list length";
      return false;
    }

    $edge_count = 0;

    foreach ($this->vertex_data as $v=>$data)
    {

      if (!array_key_exists($v,$this->adjacency_list))
      {
        echo "element of vertex_data is not in adjacency_list";
        return false;
      }

      $edge_count += count($this->adjacency_list[$v]);
    }

    if ($edge_count != $this->edge_count)
    {
      echo "edge_count != this->edge_count";
      return false;
    }

    if (($this->edge_count % 2) != 0)
    {
      echo "this->edge_count % 2 != 0";
      return false;
    }

    return true;
  }

  //Removes a vertex if it exists, and returns its data, null otherwise.
  public function removeVertex($u)
  {
    assert($this->sanityCheck());

    //If the vertex does not exist,
    if (!$this->hasVertex($u)){
      //Sanity.
      assert(!array_key_exists($u,$this->vertex_data));
      assert(!array_key_exists($u,$this->adjacency_list));
      return null;
    }

    //We need to remove all edges that this vertex belongs to.
    foreach ($this->getEdgeVertices($u) as $v)
    {
      $this->removeEdge($u,$v);
    }


    //After removing all such edges, u should have no neighbors.
    assert($this->countVertexEdges($u) == 0);

    //sanity.
    assert(array_key_exists($u,$this->vertex_data));
    assert(array_key_exists($u,$this->adjacency_list));

    //remember the data.
    $data = $this->vertex_data[$u];

    //remove the vertex from the data array.
    unset($this->vertex_data[$u]);
    //remove the vertex from the adjacency list.
    unset($this->adjacency_list[$u]);

    assert($this->sanityCheck());

    return $data;
  }

  public function getVertexCount()
  {
    assert($this->sanityCheck());
    return count($this->vertex_data);
  }

  public function getEdgeCount()
  {
    assert($this->sanityCheck());

    //edge_count counts both (u,v) and (v,u)
    return $this->edge_count/2;
  }

  public function getVertexList($hasData=false)
  {
    $result = array();

    if ($hasData)
      foreach ($this->vertex_data as $u=>$data)
        $result[$u]=$data;
    else
      foreach ($this->vertex_data as $u=>$data)
        array_push($result,$u);

    return $result;
  }

  public function edgesAsStringArray($ordered=true)
  {
    $stringArray = array();
    foreach($this->vertex_data as $u=>$udata)
    {
      foreach($this->adjacency_list[$u] as $v=>$uv_data)
      {
        if (!$ordered || ($u < $v))
          array_push($stringArray, '('.$u.','.$v.')');
      }
    }
    return $stringArray;
  }

  public function printGraph($ordered=true)
  {
    echo "digraph G {";
    foreach($this->vertex_data as $u=>$udata)
    {
      foreach($this->adjacency_list[$u] as $v=>$uv_data)
      {
        if (!$ordered || ($u < $v))
          echo "\"". substr($u, 0, 5) ."...\"". "->" ."\"". substr($v, 0, 5) ."...\"";
      }
    }
    echo "}";
  }

}


//
//
// for ($i=0; $i<5; ++$i)
// {
//   $G->addVertex($i);
// }
//
// for ($i=5; $i<10; ++$i)
// {
//   $G->addEdge($i,$i-5);
// }
//
 $G->printGraph();
// print 'V: {'.join(', ',$G->getVertexList())."}\n";
// print 'E: {'.join(', ',$G->edgesAsStringArray())."}\n";
//
// $G->removeVertex(1);
//
// print 'V: {'.join(', ',$G->getVertexList())."}\n";
// print 'E: {'.join(', ',$G->edgesAsStringArray())."}\n";
//
// $G->removeVertex(1);
//
// print 'V: {'.join(', ',$G->getVertexList())."}\n";
// print 'E: {'.join(', ',$G->edgesAsStringArray())."}\n";

?>
