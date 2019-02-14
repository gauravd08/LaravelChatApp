<template>
  <v-layout row>
    <v-flex xs12 sm6 offset-sm3>
      <v-card class="mb-4" dark>
        <v-list>
          <v-subheader>Group Chat</v-subheader>
          <v-divider></v-divider>
          <v-list-title class="p-3" v-for="message in allMessages" :key="message.id">
            <v-layout :align-end="(user.id!==message.user_id)" column>
              <v-flex>
                <v-layout column>
                  <v-flex>
                    <span class="small font-italic">{{message.user.name}}</span>
                  </v-flex>
                  <v-flex>
                    <v-chip :color="(user.id!==message.user_id)?'red':'green'" text-color="white">
                      <v-list-title-content>{{message.message}}</v-list-title-content>
                    </v-chip>
                  </v-flex>

                  <v-flex class="caption font italic">{{message.created_at}}</v-flex>
                </v-layout>
              </v-flex>
            </v-layout>
          </v-list-title>
          <p v-if="typingFriend.name">{{typingFriend.name}} is typing</p>
        </v-list>
      </v-card>
    </v-flex>

    <v-footer height="auto" fixed color="grey">
      <v-layout row>
        <v-flex xs6 offset-xs3 justify-center align-center>
          <v-text-field
            rows="2"
            @keyup.enter="sendMessage"
            v-model="message"
            label="Enter message"
            single-line
            @keydown="onTyping"
          ></v-text-field>
        </v-flex>

        <v-flex xs2>
          <v-btn @click="sendMessage" dark class="mt-3 ml-2 white--text" small color="green">send</v-btn>
        </v-flex>
      </v-layout>
    </v-footer>
  </v-layout>
</template>

<script>
export default {
  props: ["user"],
  data() {
    return {
      message: null,
      allMessages: [],
      typingFriend: {},
      typingClock: null
    };
  },
  methods: {
    onTyping() {
      Echo.private("lchat").whisper("typing", {
        user: this.user
      });
    },
    sendMessage() {
      //check if there are messages
      if (!this.message) {
        return alert("please enter the message");
      }

      //send post request
      axios.post("/messages", { message: this.message }).then(response => {
        //this.allMessages.push(this.message);
        this.message = null;
        this.allMessages.push(response.data.message);
        // console.log(this.allMessages);
        //return false;
        this.fetchMessages();
      });
    },
    fetchMessages() {
      axios.get("/messages").then(response => {
        this.allMessages = response.data;
        setTimeout(this.scrollToEnd, 100);
      });
    },
    scrollToEnd() {
      scrollTo(0, 99999);
    }
  },
  mounted() {},
  created() {
    this.fetchMessages();
    Echo.private("lchat")
      .listenForWhisper("typing", e => {
        console.log("whispring");
        this.typingFriend = e.user;
        if (this.typingClock) clearTimeout;
        this.typingClock = setTimeout(() => {
          this.typingFriend = {};
        }, 3000);
        setTimeout(this.scrollToEnd, 100);
      })
      .listen("MessageSent", e => {
        // this.allMessages.push(e.message);
        this.allMessages.push({
          message: e.message.message,
          user: e.user
        });
        setTimeout(this.scrollToEnd, 100);
      });
  }
};
</script>